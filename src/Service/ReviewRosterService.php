<?php

/**
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2020 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\Service;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Evaluation\Entity\Reviewer\Contact as ReviewContact;
use Evaluation\Service\ReviewRoster\CrGenerator;
use Evaluation\Service\ReviewRoster\Logger;
use Evaluation\Service\ReviewRoster\PoFppPprGenerator;
use Evaluation\Service\ReviewRoster\PoFppPprOnlineGenerator;
use InvalidArgumentException;
use Organisation\Entity\Parent\Organisation as ParentOrganisation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader;
use Program\Service\CallService;
use Project\Entity\Project;
use Project\Entity\Report\Report;
use Project\Search\Service\ProjectSearchService;
use Project\Service\ProjectService;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_push;
use function array_sum;
use function count;
use function end;
use function file_exists;
use function in_array;
use function key;
use function ksort;
use function sprintf;
use function strtolower;
use function unlink;

/**
 * Class ReviewRosterService
 *
 * @package Evaluation\Service
 */
class ReviewRosterService
{
    public const REVIEWER_ASSIGNED = 1111;
    public const REVIEWER_PRIMARY = 2222;
    public const REVIEWER_SPARE = 3333;
    public const REVIEWER_EXTRA_SPARE = 3344;
    public const REVIEWER_EXTRA = 4444;
    public const REVIEWER_IGNORED = -1;
    public const REVIEWER_UNASSIGNED = 0;

    private const MIN_REVIEWERS_ASSIGNED = 3;   // At least 3 reviewers should be assigned to each project by default
    private const MAX_RETRIES = 25; // Maximum attempts to generate a correct roster

    public static array $scoreBoost = [
        ReviewerService::TYPE_PO  => 1,
        ReviewerService::TYPE_CR  => 2,
        ReviewerService::TYPE_FPP => 3,
        ReviewerService::TYPE_PPR => 3,
        ReviewerService::TYPE_R   => 5,
        ReviewerService::TYPE_FE  => 6,
        ReviewerService::TYPE_PR  => 10, // Preferred reviewers have the highest boost
    ];

    private CallService $callService;
    private ProjectService $projectService;
    private ProjectSearchService $projectSearchService;
    private ReviewerService $reviewerService;
    private EntityManager $entityManager;
    private int $reviewersPerProject;
    private bool $includeSpareReviewers;
    private bool $onlineReview;
    private float $avgReviewActivityScore;
    private Logger $logger;

    public function __construct(
        CallService $callService,
        ProjectService $projectService,
        ProjectSearchService $projectSearchService,
        ReviewerService $reviewerService,
        EntityManager $entityManager
    ) {
        $this->callService = $callService;
        $this->projectService = $projectService;
        $this->projectSearchService = $projectSearchService;
        $this->reviewerService = $reviewerService;
        $this->entityManager = $entityManager;
        $this->logger = new Logger();
    }

    /**
     * @param string $type                    Constants defined in Evaluation\Service\ReviewerService
     * @param array $config                   Config data output by parseConfigFile()
     * @param int $reviewersPerProject        Minimum number of reviewers assigned per project
     * @param bool $includeSpareReviewers     Include spare reviewers in the minimum number of reviewers assigned
     * @param bool $onlineReview              Whether the review is online (no rounds) or physical (use review rounds)
     * @param int|null $forceProjectsPerRound Overrule the calculated number of projects per round
     *
     * @return array
     * @throws EntityNotFoundException
     */
    public function generateRosterData(
        string $type,
        array $config,
        int $reviewersPerProject = self::MIN_REVIEWERS_ASSIGNED,
        bool $includeSpareReviewers = false,
        bool $onlineReview = false,
        ?int $forceProjectsPerRound = null
    ): array
    {
        $this->reviewersPerProject = $reviewersPerProject;
        $this->includeSpareReviewers = $includeSpareReviewers;
        $this->onlineReview = $onlineReview;
        $this->avgReviewActivityScore = 1.0;
        $rosterData = [];
        $projects = $this->getProjects($type, $config['projects']);
        $allReviewers = array_merge($config['present'], $config['spare']);
        ksort($allReviewers);

        // Apply score boosts and penalties based on review history, preferred and ignored reviewers
        $projectReviewerScores = $this->generateProjectReviewerScores($config, $projects);

        $this->logger->log(
            __LINE__,
            sprintf(
                'Type: %s, Online: %s, Reviewers per project: %d, Include spare reviewers: %s, Projects per round: %s',
                $type,
                ($onlineReview ? 'Yes' : 'No'),
                $reviewersPerProject,
                ($includeSpareReviewers ? 'Yes' : 'No'),
                (($forceProjectsPerRound === null) ? 'Auto' : (string) $forceProjectsPerRound)
            )
        );

        // Generate the roster data
        $this->logger->log(__LINE__, 'Generate the roster data');
        if ($type === ReviewerService::TYPE_CR) {
            $rosterData = $this->generateCrRosterData($config, $projectReviewerScores);
        } elseif (in_array($type, [ReviewerService::TYPE_PO, ReviewerService::TYPE_FPP, ReviewerService::TYPE_PPR])) {
            $rosterData = $this->generatePoFppPprRosterData($config, $projectReviewerScores, $forceProjectsPerRound);
        }

        //Logger::dumpRoundAssignments($config, $rosterData); die();

        // Test the roster data and re-generate when required
        if (!$this->testRosterAssignments($rosterData)) {
            $try = 1;
            while ($try <= self::MAX_RETRIES) {
                $this->logger->log(__LINE__, sprintf('Retry %d of %d', $try, self::MAX_RETRIES));
                if ($type === ReviewerService::TYPE_CR) {
                    $rosterData = $this->generateCrRosterData($config, $projectReviewerScores);
                } elseif (
                in_array(
                    $type,
                    [ReviewerService::TYPE_PO, ReviewerService::TYPE_FPP, ReviewerService::TYPE_PPR]
                )
                ) {
                    $rosterData = $this->generatePoFppPprRosterData($config, $projectReviewerScores, $forceProjectsPerRound);
                }
                if ($this->testRosterAssignments($rosterData)) {
                    return $rosterData;
                }
                $try++;
            }
        }

        return $rosterData;
    }

    /**
     * Get a list of projects depending on the roster type
     *
     * @param string $type
     * @param array $projectConfig
     *
     * @return array
     * @throws EntityNotFoundException
     */
    private function getProjects(string $type, array $projectConfig = [])
    {
        $projects = [];

        // Progress report review
        if ($type === ReviewerService::TYPE_PPR) {
            $now = new DateTime();
            $previousSemester = (((int) $now->format('m')) < 6) ? 2 : 1;
            $year = ($previousSemester === 2) ? ((int) $now->format('Y') - 1) : ((int) $now->format('Y'));
            $reports = $this->entityManager->getRepository(Report::class)->findBy([
                'year'     => $year,
                'semester' => $previousSemester
            ]);

            foreach ($reports as $report) {
                $projects[] = $report->getProject();
            }
        } // Project version review
        elseif (in_array($type, [ReviewerService::TYPE_PO, ReviewerService::TYPE_FPP], false)) {
            $calls = $this->callService->findOpenCall();
            $currentCall = $calls->getFirst() ?? $calls->getUpcoming();
            $filter = [
                'program_call_id'     => $currentCall->getId(),
                'latest_version_type' => strtolower($type)
            ];

            // Keep a list of scores determined by the number of review activities
            $this->projectSearchService->setSearch('', ['project_id']);
            $this->projectSearchService->getQuery()->setRows(1000);
            $this->projectSearchService->getQuery()->setFields(['project_id']);
            foreach ($filter as $property => $value) {
                $this->projectSearchService->addFilterQuery($property, $value);
            }
            $projectIDs = [];
            foreach ($this->projectSearchService->getResultSet() as $projectData) {
                $projectIDs[] = $projectData['project_id'];
            }

            // Works with a small to moderate amount of project IDs
            $projects = $this->entityManager->getRepository(Project::class)->findBy(['id' => $projectIDs]);
        } // Assignment for change requests
        elseif ($type === ReviewerService::TYPE_CR) {
            $projects = $this->projectService->findActiveProjectsForReviewRoster();
        }

        // Manually include and/or exclude projects from the configuration file
        $projectIndex = [];
        foreach ($projects as $index => $project) {
            $projectIndex[$project->getProject()] = $index;
        }
        foreach ($projectConfig['included'] as $projectName) {
            if (!isset($projectIndex[$projectName]) && !in_array($projectName, $projectConfig['excluded'])) {
                $project = $this->entityManager->getRepository(Project::class)->findOneBy([
                    'project' => $projectName
                ]);
                if ($project instanceof Project) {
                    $projects[] = $project;
                    end($projects);
                    $projectIndex[$project->getProject()] = key($projects);
                } else {
                    throw new EntityNotFoundException('Could not find project ' . $projectName);
                }
            }
        }
        foreach ($projectConfig['excluded'] as $projectName) {
            if (isset($projectIndex[$projectName])) {
                unset($projects[$projectIndex[$projectName]]);
            }
        }

        return $projects;
    }

    // Apply score boosts and penalties based on review history, preferred and ignored reviewers
    private function generateProjectReviewerScores(array $config, array $projects): array
    {
        $projectReviewerScores = [];
        $projectReviewActivity = [];

        /**
         * @var int $projectKey
         * @var Project $project
         */
        foreach ($projects as $projectKey => $project) {
            $ignoredReviewers = $this->reviewerService->getIgnoredReviewers($project);
            $reviewHistory = $this->reviewerService->getReviewHistory($project);
            $preferredReviewers = $this->reviewerService->getPreferredReviewers($project);

            $projectReviewerScores[$projectKey] = [
                'data'   => [
                    'number'  => $project->getNumber(),
                    'name'    => $project->getProject(),
                    'call'    => $project->getCall()->shortName(),
                    'history' => $reviewHistory,
                    'ignored' => $ignoredReviewers
                ],
                'scores' => []
            ];

            // Init scores
            $reviewerData = array_merge($config['present'], $config['spare']);
            foreach (array_keys($reviewerData) as $reviewer) {
                $projectReviewerScores[$projectKey]['scores'][$reviewer] = 0;
            }

            // Assign score boost based on previous reviews
            $count = 1;
            $totalReviews = count($reviewHistory);
            $projectReviewActivity[$project->getId()] = $totalReviews;
            foreach ($reviewHistory as $reviewLine) {
                foreach ($reviewLine as $type => $reviewers) {
                    foreach ($reviewers as $reviewer) {
                        $score = self::$scoreBoost[$type];
                        // Older reviews have less weight
                        if ($count < $totalReviews) {
                            $score = $score / ($totalReviews - $count);
                        }
                        if (array_key_exists($reviewer, $projectReviewerScores[$projectKey]['scores'])) {
                            $projectReviewerScores[$projectKey]['scores'][$reviewer] = $score;
                        }
                    }
                    $count++;
                }
            }
            // Assign score boost for preferred reviewers
            foreach ($preferredReviewers as $reviewer) {
                if (array_key_exists($reviewer, $projectReviewerScores[$projectKey]['scores'])) {
                    $projectReviewerScores[$projectKey]['scores'][$reviewer]
                        += self::$scoreBoost[ReviewerService::TYPE_PR];
                }
            }
            // Assign a negative score to ignored reviewers
            foreach ($ignoredReviewers as $reviewer) {
                if (array_key_exists($reviewer, $projectReviewerScores[$projectKey]['scores'])) {
                    $projectReviewerScores[$projectKey]['scores'][$reviewer] = self::REVIEWER_IGNORED;
                }
            }
        }

        // Normalizing the weight for this roster
        // Principle:   Compute the average score per project for this roster, and then the scores per project
        //              are normalized by it
        // Idea:        Avoid that brand new projects are hindered compared to old projects with lots of PPRs,
        //              CRs, etc. => we want continuity for ALL projects!
        $projectReviewActivityScores = count($projectReviewActivity);
        if ($projectReviewActivityScores > 0) {
            $this->avgReviewActivityScore = array_sum($projectReviewActivity) / $projectReviewActivityScores;
        }
        foreach ($projectReviewerScores as &$reviewerScores) {
            foreach ($reviewerScores['scores'] as &$reviewerScore) {
                if ($reviewerScore > 0) {
                    $reviewerScore /= $this->avgReviewActivityScore;
                }
            }
        }

        return $projectReviewerScores;
    }

    private function generateCrRosterData(array $config, array $projectReviewerScores): array
    {
        $crGenerator = new CrGenerator($config, $projectReviewerScores, $this->reviewersPerProject);
        $generatedRosterData = $crGenerator->generate();
        $this->logger->merge($generatedRosterData->getLogger());

        return $generatedRosterData->getData();
    }

    private function generatePoFppPprRosterData(
        array $config,
        array $projectReviewerScores,
        ?int $forceProjectsPerRound = null
    ): array
    {
        if ($this->onlineReview) {
            $generator = new PoFppPprOnlineGenerator($config, $projectReviewerScores, $this->reviewersPerProject);
        } else {
            $generator = new PoFppPprGenerator(
                $config,
                $projectReviewerScores,
                $this->reviewersPerProject,
                $this->avgReviewActivityScore,
                $this->includeSpareReviewers,
                $forceProjectsPerRound
            );
        }
        $generatedRosterData = $generator->generate();
        $this->logger->merge($generatedRosterData->getLogger());

        return $generatedRosterData->getData();
    }

    /**
     * Test project assignment for edge cases when not enough reviewers get assigned to a project
     *
     * @param array $rosterData
     *
     * @return bool
     */
    private function testRosterAssignments(array $rosterData): bool
    {
        $assigned = [
            self::REVIEWER_ASSIGNED,
            self::REVIEWER_PRIMARY,
            self::REVIEWER_EXTRA
        ];
        if ($this->includeSpareReviewers) {
            array_push($assigned, self::REVIEWER_SPARE, self::REVIEWER_EXTRA_SPARE);
        }

        foreach ($rosterData as $projects) {
            foreach ($projects as $projectIndex => $project) {
                $assignedReviewersPerProject = 0;
                foreach ($project['scores'] as $handle => $score) {
                    if (in_array($score, $assigned)) {
                        $assignedReviewersPerProject++;
                    }
                }
                if ($assignedReviewersPerProject < $this->reviewersPerProject) {
                    $this->logger->log(
                        __LINE__,
                        sprintf(
                            'Roster test failed for %s: %d reviewers assigned of the required %d',
                            $project['data']['number'] . ' ' . $project['data']['name'],
                            $assignedReviewersPerProject,
                            $this->reviewersPerProject
                        )
                    );
                    return false;
                }
            }
        }
        $this->logger->log(__LINE__, 'Roster test completed successfully.');

        return true;
    }

    public function parseConfigFile(string $configFile): array
    {
        $config = [];
        if (!file_exists($configFile)) {
            throw new InvalidArgumentException('Excel config file not found.');
        }

        $inputFileType = IOFactory::identify($configFile);
        /** @var Reader\BaseReader $reader */
        $reader = IOFactory::createReader($inputFileType);
        $reader->setReadDataOnly(true);
        $excel = $reader->load($configFile);

        /**
         * 1st sheet: Present reviewers
         * 2nd sheet: Spare reviewers
         * 3rd sheet: Excluded reviewers
         * 4th sheet: Included and excluded projects
         */
        $startRow = 2;
        $keys = [0 => 'present', 1 => 'spare', 2 => 'excluded', 3 => 'projects'];
        foreach ($keys as $section) {
            $config[$section] = [];
        }
        foreach ($excel->getAllSheets() as $sheetIndex => $sheet) {
            $highestRow = $sheet->getHighestRow();

            // Reviewer data
            if ($sheetIndex < 3) {
                for ($row = $startRow; $row <= $highestRow; $row++) {
                    $handle = $sheet->getCell('A' . $row)->getValue();
                    if (!empty($handle)) {
                        $reviewContact = $this->entityManager->getRepository(ReviewContact::class)
                            ->findOneBy(['handle' => $handle]);
                        // Check whether the review contact has been found and has the same case XyZ / XYZ would both
                        // match because by default in MySQL non-binary string comparisons are case insensitive
                        if ($reviewContact instanceof ReviewContact && ($handle === $reviewContact->getHandle())) {
                            $organisation = $reviewContact->getContact()->getContactOrganisation()->getOrganisation();
                            $parent = ($organisation->getParentOrganisation() instanceof ParentOrganisation)
                                ? $organisation->getParentOrganisation()->getParent()->getOrganisation()
                                    ->getOrganisation()
                                : null;
                            $config[$keys[$sheetIndex]][$handle] = [
                                'name'         => $reviewContact->getContact()->parseFullName(),
                                'organisation' => $organisation->getOrganisation(),
                                'parent'       => $parent,
                                'present'      => ($sheetIndex === 0),
                                'risky'        => ((int)$sheet->getCell('B' . $row)->getValue() === 1),
                                'experienced'  => ((int)$sheet->getCell('C' . $row)->getValue() === 1),
                                'weight'       => (float)$sheet->getCell('D' . $row)->getValue(),
                                'availability' => (float)$sheet->getCell('E' . $row)->getValue(),
                            ];
                        } else {
                            throw new EntityNotFoundException('Could not find a review contact for handle ' . $handle);
                        }
                    }
                }
            } // Project data
            elseif ($sheetIndex === 3) {
                if (empty($config[$keys[$sheetIndex]])) {
                    $config[$keys[$sheetIndex]] = [
                        'included' => [],
                        'excluded' => []
                    ];
                }
                for ($row = $startRow; $row <= $highestRow; $row++) {
                    $includedProject = $sheet->getCell('A' . $row)->getValue();
                    $excludedProject = $sheet->getCell('B' . $row)->getValue();
                    if (!empty($includedProject)) {
                        $config[$keys[$sheetIndex]]['included'][] = $includedProject;
                    }
                    if (!empty($excludedProject)) {
                        $config[$keys[$sheetIndex]]['excluded'][] = $excludedProject;
                    }
                }
            }
        }

        $reader = null;
        $excel = null;
        unlink($configFile);

        return $config;
    }

    public function getLog(): array
    {
        return $this->logger->getLog();
    }
}
