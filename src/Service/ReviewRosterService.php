<?php
/**
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2019 ITEA Office (https://itea3.org)
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
use InvalidArgumentException;
use Organisation\Entity\Parent\Organisation as ParentOrganisation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader;
use Program\Service\CallService;
use Project\Entity\Project;
use Project\Entity\Report\Report;
use Project\Search\Service\ProjectSearchService;
use Project\Service\ProjectService;
use function array_diff;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_push;
use function array_rand;
use function array_shift;
use function array_slice;
use function array_sum;
use function asort;
use function ceil;
use function count;
use function current;
use function end;
use function file_exists;
use function floor;
use function in_array;
use function key;
use function krsort;
use function ksort;
use function next;
use function rand;
use function reset;
use function shuffle;
use function sprintf;
use function sqrt;
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
    private const MAX_ITERATIONS = 10;  // Maximum optimization iterations of the whole roster
    private const TEAM_RECURRENCE_FACTOR = 0.3; // In 10 rounds no more than 3x the same review team

    private const MAX_RETRIES = 25; // Maximum attempts to generate a correct roster

    private static array $scoreBoost = [
        ReviewerService::TYPE_PO => 1,
        ReviewerService::TYPE_CR => 2,
        ReviewerService::TYPE_FPP => 3,
        ReviewerService::TYPE_PPR => 3,
        ReviewerService::TYPE_R => 5,
        ReviewerService::TYPE_FE => 6,
        ReviewerService::TYPE_PR => 10, // Preferred reviewers have the highest boost
    ];

    private static array $assigned = [
        self::REVIEWER_ASSIGNED,
        self::REVIEWER_PRIMARY,
        self::REVIEWER_SPARE,
        self::REVIEWER_EXTRA_SPARE,
        self::REVIEWER_EXTRA
    ];

    private CallService $callService;
    private ProjectService $projectService;
    private ProjectSearchService $projectSearchService;
    private ReviewerService $reviewerService;
    private EntityManager $entityManager;
    private int $reviewersPerProject;
    private bool $includeSpareReviewers;
    private int $avgReviewActivityScore;
    private array $log;

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
    }

    /**
     * @param string $type Constants defined in Evaluation\Service\ReviewerService
     * @param array $config Config data output by parseConfigFile()
     * @param int $reviewersPerProject Minimum number of reviewers assigned per project
     * @param bool $includeSpareReviewers Include spare reviewers in the minimum number of reviewers assigned
     * @param int|null $forceProjectsPerRound Overrule the calculated number of projects per round
     *
     * @return array
     */
    public function generateRosterData(
        string $type,
        array $config,
        int $reviewersPerProject = self::MIN_REVIEWERS_ASSIGNED,
        bool $includeSpareReviewers = false,
        ?int $forceProjectsPerRound = null
    ): array {
        $this->reviewersPerProject = $reviewersPerProject;
        $this->includeSpareReviewers = $includeSpareReviewers;
        $this->avgReviewActivityScore = 1;
        $this->log = [];
        $rosterData = [];
        $projects = $this->getProjects($type, $config['projects']);
        $allReviewers = array_merge($config['present'], $config['spare']);
        ksort($allReviewers);

        // Apply score boosts and penalties based on review history, preferred and ignored reviewers
        $projectReviewerScores = $this->generateProjectReviewerScores($projects, $allReviewers);
        $roundAssignments = [];

        $this->log(
            __LINE__,
            sprintf(
                'Type: %s, Reviewers per project: %d, Include spare reviewers: %s, Projects per round: %s',
                $type,
                $reviewersPerProject,
                ($includeSpareReviewers ? 'Yes' : 'No'),
                (($forceProjectsPerRound === null) ? 'Auto' : (string)$forceProjectsPerRound)
            )
        );

        // Generate the roster data
        if ($type === ReviewerService::TYPE_CR) {
            $rosterData = $this->generateCrRosterData($projectReviewerScores, $allReviewers);
        } elseif (in_array($type, [ReviewerService::TYPE_PO, ReviewerService::TYPE_FPP, ReviewerService::TYPE_PPR])) {
            // Basic round assignment. Just divide the projects over the rounds in the order they came.
            $reviewerCount = $this->includeSpareReviewers ? count($allReviewers) : count($config['present']);
            $projectCount = count($projects);
            $projectsPerRound = $this->preCalculateRounds(
                $projectCount,
                $this->reviewersPerProject,
                $reviewerCount,
                $forceProjectsPerRound
            );

            $roundAssignments = $this->generateBasicRoundAssignment($projectReviewerScores, $projectsPerRound);
            $this->log(
                __LINE__,
                sprintf(
                    'Projects: %d, Reviewers: %d, Rounds: %d, Projects per round: %d',
                    $projectCount,
                    $reviewerCount,
                    count($projectsPerRound),
                    reset($projectsPerRound)
                )
            );

            // Optimize the distribution of score boosts and ignores among the review rounds
            $this->log(__LINE__, 'Optimizing the distribution of score boosts and ignores among the review rounds');
            $roundAssignments = $this->optimizeDistribution($roundAssignments, $projectsPerRound);

            //$this->dumpRoundAssignments($roundAssignments, $allReviewers); die();

            $this->log(__LINE__, 'Generate the roster data');
            $rosterData = $this->generatePoFppPprRosterData($roundAssignments, $allReviewers);
        }

        //$this->dumpRoundAssignments($rosterData, $allReviewers);

        if (!$this->testRosterAssignments($rosterData)) {
            $try = 1;
            while ($try <= self::MAX_RETRIES) {
                $this->log(__LINE__, sprintf('Retry %d of %d', $try, self::MAX_RETRIES));
                if ($type === ReviewerService::TYPE_CR) {
                    $rosterData = $this->generateCrRosterData($projectReviewerScores, $allReviewers);
                } elseif (in_array(
                    $type,
                    [ReviewerService::TYPE_PO, ReviewerService::TYPE_FPP, ReviewerService::TYPE_PPR]
                )
                ) {
                    $rosterData = $this->generatePoFppPprRosterData($roundAssignments, $allReviewers);
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
     */
    private function getProjects(string $type, array $projectConfig = [])
    {
        $projects = [];

        // Progress report review
        if ($type === ReviewerService::TYPE_PPR) {
            $now = new DateTime();
            $previousSemester = (((int)$now->format('m')) < 6) ? 2 : 1;
            $year = ($previousSemester === 2) ? ((int)$now->format('Y') - 1) : ((int)$now->format('Y'));
            $reports = $this->entityManager->getRepository(Report::class)->findBy(
                [
                    'year' => $year,
                    'semester' => $previousSemester
                ]
            );

            foreach ($reports as $report) {
                $projects[] = $report->getProject();
            }
        } // Project version review
        elseif (in_array($type, [ReviewerService::TYPE_PO, ReviewerService::TYPE_FPP], false)) {
            $calls = $this->callService->findOpenCall();
            $currentCall = $calls->getFirst() ?? $calls->getUpcoming();
            $filter = [
                'program_call_id' => $currentCall->getId(),
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
                $project = $this->entityManager->getRepository(Project::class)
                    ->findOneBy(['project' => $projectName]);
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

    /**
     * Apply score boosts and penalties based on review history, preferred and ignored reviewers
     *
     * @param array|Project[] $projects
     * @param array $allReviewers
     *
     * @return array
     */
    private function generateProjectReviewerScores(array $projects, array $allReviewers): array
    {
        $projectReviewerScores = [];
        $projectReviewActivity = [];

        foreach ($projects as $projectKey => $project) {
            $ignoredReviewers = $this->reviewerService->getIgnoredReviewers($project);
            $reviewHistory = $this->reviewerService->getReviewHistory($project);
            $preferredReviewers = $this->reviewerService->getPreferredReviewers($project);

            $projectReviewerScores[$projectKey] = [
                'data' => [
                    'number' => $project->getNumber(),
                    'name' => $project->getProject(),
                    'call' => $project->getCall()->shortName(),
                    'history' => $reviewHistory,
                    'ignored' => $ignoredReviewers
                ],
                'scores' => []
            ];

            // Init scores
            foreach (array_keys($allReviewers) as $reviewer) {
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

    private function log(int $line, string $message): void
    {
        $this->log[] = ['l' => $line, 'm' => $message];
    }

    /**
     * Generate CR roster from call assignments
     *
     * @param array $projectReviewerScores
     * @param array $reviewerData
     *
     * @return array
     */
    private function generateCrRosterData(array $projectReviewerScores, array $reviewerData): array
    {
        // Init assignment data based on the project reviewer scores array wiping out all boosts keeping the ignores
        $assignments = $projectReviewerScores;
        foreach ($assignments as &$assignmentsProjectData) {
            foreach ($assignmentsProjectData['scores'] as &$assignmentScore) {
                if ($assignmentScore > 0) {
                    $assignmentScore = self::REVIEWER_UNASSIGNED;
                }
            }
        }

        // Determine the best matches per project
        $bestMatchesByProject = [];
        foreach ($projectReviewerScores as $projectIndex => $project) {
            $bestMatchesByProject[$projectIndex] = [];
            $highestScore = 0;
            foreach ($project['scores'] as $handle => $score) {
                if ($score > $highestScore) {
                    $bestMatchesByProject[$projectIndex] = [$handle];
                    $highestScore = $score;
                } elseif ($score === $highestScore) {
                    $bestMatchesByProject[$projectIndex][] = $handle;
                }
            }
            shuffle($bestMatchesByProject[$projectIndex]);
        }

        // Init reviewer load
        $reviewerLoad = [];
        $reviewers = 0;
        foreach (array_keys($reviewerData) as $handle) {
            $reviewerLoad[$handle] = 0;
            $reviewers++;
        }

        // Assign reviewers
        foreach ($assignments as $projectIndex => &$assignment) {
            $reviewersAssigned = [];
            $hasPrimaryReviewer = false;

            // If future evaluation is set, get reviewers from there
            $lastHistoryItem = end($projectReviewerScores[$projectIndex]['data']['history']);
            if ($lastHistoryItem && isset($lastHistoryItem[ReviewerService::TYPE_FE])) {
                foreach ($lastHistoryItem[ReviewerService::TYPE_FE] as $handle) {
                    if (count($reviewersAssigned) === $this->reviewersPerProject) {
                        break;
                    }
                    if (isset($reviewerData[$handle])
                        // Prevent rare cases where a preferred reviewer is also an ignored one (after org merge)
                        && ($projectReviewerScores[$projectIndex]['scores'][$handle] !== self::REVIEWER_IGNORED)
                        // Prevent reviewers from the same company being added
                        && !$this->sameOrganisation($handle, $reviewersAssigned, $reviewerData)
                    ) {
                        $reviewersAssigned[] = $handle;
                        $reviewerLoad[$handle]++;
                        $assignment['scores'][$handle] = self::REVIEWER_ASSIGNED;
                        $this->log(
                            __LINE__,
                            sprintf(
                                '%s: %s assigned based on FE',
                                $assignment['data']['number'] . ' ' . $assignment['data']['name'],
                                $handle
                            )
                        );
                    }
                }
            } // Add the highest scoring match per project
            elseif (!empty($bestMatchesByProject[$projectIndex])) {
                $handle = reset($bestMatchesByProject[$projectIndex]);
                $reviewersAssigned[] = $handle;
                $reviewerLoad[$handle]++;
                $assignment['scores'][$handle] = self::REVIEWER_ASSIGNED;
                $this->log(
                    __LINE__,
                    sprintf(
                        '%s: %s assigned based on best scoring project matches',
                        $assignment['data']['number'] . ' ' . $assignment['data']['name'],
                        $handle
                    )
                );
            }

            // Make one of the above reviewers the primary reviewer.
            // Inexperienced reviewers should not become prime reviewer.
            if (!empty($reviewersAssigned)) {
                $handle = $reviewersAssigned[array_rand($reviewersAssigned)];
                $assignment['scores'][$handle] = self::REVIEWER_PRIMARY;
                $hasPrimaryReviewer = true;
                $this->log(
                    __LINE__,
                    sprintf(
                        '%s: %s made primary reviewer randomly selected from FE and best project matches',
                        $assignment['data']['number'] . ' ' . $assignment['data']['name'],
                        $handle
                    )
                );
            }

            // Add randomly picked other reviewers based on reviewer workload
            $iteration = 1; // Fail safe to prevent infinite loop
            asort($reviewerLoad);
            while ((count($reviewersAssigned) < $this->reviewersPerProject) && ($iteration <= $reviewers)) {
                foreach (array_keys($reviewerLoad) as $handle) {
                    // Add reviewers with low load and not from the same organisation as reviewers already assigned
                    if (($assignment['scores'][$handle] === 0)
                        && !$this->sameOrganisation($handle, $reviewersAssigned, $reviewerData)
                    ) {
                        $reviewersAssigned[] = $handle;
                        $reviewerLoad[$handle]++;
                        $assignment['scores'][$handle] = self::REVIEWER_ASSIGNED;
                        asort($reviewerLoad);
                        $this->log(
                            __LINE__,
                            sprintf(
                                '%s: %s assigned randomly based on reviewer workload',
                                $assignment['data']['number'] . ' ' . $assignment['data']['name'],
                                $handle
                            )
                        );
                        break;
                    }
                }
                $iteration++;
            }

            // No primary reviewer has been assigned based on experience or FE label. Just pick a random one.
            if (!$hasPrimaryReviewer) {
                $handle = $reviewersAssigned[array_rand($reviewersAssigned)];
                $assignment['scores'][$handle] = self::REVIEWER_PRIMARY;
                $this->log(
                    __LINE__,
                    sprintf(
                        '%s: No primary reviewer based on experience or FE label, randomly assigned %s as primary reviewer',
                        $assignment['data']['number'] . ' ' . $assignment['data']['name'],
                        $handle
                    )
                );
            }
        }

        // Sort by call DESC
        $rosterData = [];
        foreach ($assignments as $assignment1) {
            if (!isset($rosterData[$assignment1['data']['call']])) {
                $rosterData[$assignment1['data']['call']] = [];
            }
            $rosterData[$assignment1['data']['call']][] = $assignment1;
        }
        krsort($rosterData);

        //$this->dumpRoundAssignments($rosterData, $reviewerData); die();

        return $rosterData;
    }

    /**
     * Check whether reviewers are from the same (parent) organisation
     *
     * @param string $handle
     * @param array $otherHandles
     * @param array $reviewerData
     *
     * @return bool
     */
    private function sameOrganisation(string $handle, array $otherHandles, array $reviewerData): bool
    {
        foreach ($otherHandles as $otherHandle) {
            if (($reviewerData[$handle]['organisation'] === $reviewerData[$otherHandle]['organisation'])
                || (
                    ($reviewerData[$handle]['parent'] !== null)
                    && ($reviewerData[$handle]['parent'] === $reviewerData[$otherHandle]['parent'])
                )
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Roughly pre-calculate review rounds. This is a heavily simplified version of the original c++ one.
     * This assumes full availability for each reviewer, not taking into account the rounds not available
     * from start or end
     *
     * @param int $numberOfProjects
     * @param int $minReviewersAssigned
     * @param int $totalReviewers
     * @param int|null $forceProjectsPerRound
     *
     * @return array
     */
    private function preCalculateRounds(
        int $numberOfProjects,
        int $minReviewersAssigned,
        int $totalReviewers,
        ?int $forceProjectsPerRound = null
    ): array {
        $projectsPerRound = [];
        $maxProjectsPerRound = floor($totalReviewers / $minReviewersAssigned);
        if (($forceProjectsPerRound !== null) && ($forceProjectsPerRound < $maxProjectsPerRound)) {
            $maxProjectsPerRound = $forceProjectsPerRound;
        }
        $rounds = ceil(($numberOfProjects / $maxProjectsPerRound));

        // As long as there are enough projects, fill the round to the maximum
        for ($round = 1; $round <= $rounds; $round++) {
            if ($numberOfProjects >= $maxProjectsPerRound) {
                $projectsPerRound[$round] = $maxProjectsPerRound;
                $numberOfProjects -= $maxProjectsPerRound;
            } // Last round with the leftover projects
            else {
                $projectsPerRound[$round] = $numberOfProjects;
                break;
            }
        }

        return $projectsPerRound;
    }

    /**
     * Basic round assignment. Just divide the projects over the rounds in the order they came.
     *
     * @param array $projectReviewerScores
     * @param array $projectsPerRound
     *
     * @return array
     */
    private function generateBasicRoundAssignment(array $projectReviewerScores, array $projectsPerRound): array
    {
        $groupAssignments = [];
        reset($projectReviewerScores);
        foreach ($projectsPerRound as $round => $numberOfProjects) {
            if (!isset($groupAssignments[$round])) {
                $groupAssignments[$round] = [];
            }
            for ($i = 0; $i < $numberOfProjects; $i++) {
                $groupAssignments[$round][] = current($projectReviewerScores);
                next($projectReviewerScores);
            }
        }

        return $groupAssignments;
    }

    /**
     * Optimize the distribution of boosts and ignores among the rounds for better reviewer assignment.
     *
     * @param array $roundAssignments
     * @param array $projectsPerRound
     *
     * @return array
     */
    private function optimizeDistribution(array $roundAssignments, array $projectsPerRound): array
    {
        $boostDistributionScore = $this->calculateDistributionScoreBoosts($roundAssignments);
        $ignoreDistributionScore = $this->calculateDistributionScoreIgnores($roundAssignments);

        $hasImproved = true;
        for ($iteration = 1; (($iteration <= self::MAX_ITERATIONS) && $hasImproved); $iteration++) {
            $boostDistributionScoreNew = $boostDistributionScore;
            $ignoreDistributionScoreNew = $ignoreDistributionScore;

            // No need to iterate the last round, as there's no next round to swap projects with
            $totalRounds = count($roundAssignments);
            for ($round = 1; $round < $totalRounds; $round++) {
                // Iterate primary projects
                for ($index1 = 0; $index1 < $projectsPerRound[$round]; $index1++) {
                    // For each project, iterate the next rounds
                    $project1 = $roundAssignments[$round][$index1];
                    for ($nextRound = $round + 1; $nextRound <= $totalRounds; $nextRound++) {
                        // For each next round, iterate its projects
                        for ($index2 = 0; $index2 < $projectsPerRound[$nextRound]; $index2++) {
                            $roundAssignmentsNew = $roundAssignments;
                            $project2 = $roundAssignments[$nextRound][$index2];
                            // Make the project swap
                            $roundAssignmentsNew[$nextRound][$index2] = $project1;
                            $roundAssignmentsNew[$round][$index1] = $project2;
                            // Get the new scores for the group assignment with the swapped project
                            $boostDistributionScoreTest = $this->calculateDistributionScoreBoosts($roundAssignmentsNew);
                            $ignoreDistributionScoreTest = $this->calculateDistributionScoreIgnores(
                                $roundAssignmentsNew
                            );

                            // Have the scores improved after the swap?
                            $hasImprovedAfterSwitch = (
                                // At least one value should have improved
                                (($boostDistributionScoreTest <= $boostDistributionScoreNew)
                                    && ($ignoreDistributionScoreTest <= $ignoreDistributionScoreNew))
                                // Only improvement when BOTH values are NOT the same as their previous value
                                && !(($boostDistributionScoreNew === $boostDistributionScoreTest)
                                    && ($ignoreDistributionScoreNew === $ignoreDistributionScoreTest))
                            );

                            // Swap resulted in neutral or improvement, update the scores and group assignment
                            if ($hasImprovedAfterSwitch) {
                                //$perc1 = (($boostDistributionScoreNew-$boostDistributionScoreTest)/$boostDistributionScoreNew)*100;
                                //$perc2 = (($ignoreDistributionScoreNew-$ignoreDistributionScoreTest)/$ignoreDistributionScoreNew)*100;
                                //echo $boostDistributionScoreTest.' - '.$perc1.'<br>';
                                //echo $ignoreDistributionScoreTest.' - '.$perc2.'<br>';
                                $boostDistributionScoreNew = $boostDistributionScoreTest;
                                $ignoreDistributionScoreNew = $ignoreDistributionScoreTest;
                                $roundAssignments = $roundAssignmentsNew;
                                // State has changed, so on to the next $project1
                                break 2;
                            }
                        }
                    }
                }
            }

            //echo '---------- '.$iteration.' ----------<br>';

            $hasImproved = (
                // At least one value should have improved
                (($boostDistributionScoreNew <= $boostDistributionScore)
                    && ($ignoreDistributionScoreNew <= $ignoreDistributionScore))
                // Only improvement when BOTH values are NOT the same as their previous value
                && !(($boostDistributionScoreNew === $boostDistributionScore)
                    && ($ignoreDistributionScoreNew === $ignoreDistributionScore))
            );
            if ($hasImproved) {
                $boostDistributionScore = $boostDistributionScoreNew;
                $ignoreDistributionScore = $ignoreDistributionScoreNew;
            }
        }

        return $roundAssignments;
    }

    /**
     * Calculate the sum of the per-reviewer standard deviations for score boosts.
     * This value can be used to determine the distribution rate of boosts in the rounds.
     * The lower the number, the better
     *
     * @param array $groupAssignments
     *
     * @return float
     * @see http://www.wisfaq.nl/pagina.asp?nummer=1754
     */
    private function calculateDistributionScoreBoosts(array $groupAssignments): float
    {
        // Get the average boosts and ignores
        $boostsPerReviewerPerRound = [];
        $rounds = count($groupAssignments);
        $boostStandardDeviationSum = 0;

        foreach ($groupAssignments as $round => $projects) {
            foreach ($projects as $project) {
                foreach ($project['scores'] as $reviewer => $score) {
                    if (!isset($boostsPerReviewerPerRound[$reviewer][$round])) {
                        $boostsPerReviewerPerRound[$reviewer][$round] = 0;
                    }
                    if ($score > 0) { // Add score boost
                        $boostsPerReviewerPerRound[$reviewer][$round] += $score;
                    }
                }
            }
        }

        // Calculate the boost standard deviation for each reviewer
        foreach ($boostsPerReviewerPerRound as $reviewer => $roundBoosts) {
            $totalBoostScore = array_sum($roundBoosts);

            // No need to calculate when there are no boosts in any round as the std deviation would be 0
            if ($totalBoostScore > 0) {
                $averageBoostsScorePerRound = ($totalBoostScore / $rounds);
                // Get the frequencies for the number of boosts per round
                $frequencies = [];
                foreach ($roundBoosts as $boostScore) {
                    if (!isset($frequencies[$boostScore])) {
                        $frequencies[$boostScore] = 0;
                    }
                    $frequencies[$boostScore]++;
                }
                $totalSquare = 0;
                // Calculate sum of: [frequency] * [distance_to_average]^2
                foreach ($frequencies as $boostScore => $frequency) {
                    $totalSquare += ($frequency * (($boostScore - $averageBoostsScorePerRound) ** 2));
                }
                $standardDeviation = sqrt(($totalSquare / array_sum($frequencies)));
                $boostStandardDeviationSum += $standardDeviation;
            }
        }

        return (float)$boostStandardDeviationSum;
    }

    /**
     * Debug round assignment data
     *
     * @param array $groupAssignments
     * @param array $reviewerData
     */
    /*private function dumpRoundAssignments(array $groupAssignments, array $reviewerData)
    {
        echo '<pre>'."Round\tProj\t";
        $first = true;
        $reviewerCount = 0;
        foreach ($groupAssignments as $round => $projects) {
            foreach ($projects as $project) {
                if ($first) {
                    $reviewers     = \array_keys($project['scores']);
                    $reviewerCount = \count($reviewers);
                    foreach ($reviewers as &$reviewer) {
                        $reviewer = $reviewer.'('.(int) $reviewerData[$reviewer]['experienced'].')';
                    }
                    echo \implode("\t", $reviewers)."\r\n";
                    for ($i=0; $i <= (($reviewerCount+2)*8); $i++) {
                        echo '=';
                    }
                    echo "\r\n";
                    $first = false;
                }
                echo $round."\t".\substr($project['data']['name'], 0, 6)."\t";
                \array_walk($project['scores'], function (&$item) {
                    $item = \round($item, 4);
                });
                echo \implode("\t", $project['scores']);
                echo "\r\n";
            }
            for ($i=0; $i <= (($reviewerCount+2)*8); $i++) {
                echo '=';
            }
            echo "\r\n";
        }
        echo '</pre>';
    }*/

    /**
     * Calculate the sum of the per-reviewer standard deviations for ignored status.
     * This value can be used to determine the distribution rate of ignores in the rounds.
     * The lower the number, the better
     *
     * @param array $groupAssignments
     *
     * @return float
     * @see http://www.wisfaq.nl/pagina.asp?nummer=1754
     */
    private function calculateDistributionScoreIgnores(array $groupAssignments): float
    {
        // Get the average ignores
        $ignoresPerReviewerPerRound = [];
        $rounds = count($groupAssignments);
        $ignoreStandardDeviationSum = 0;

        foreach ($groupAssignments as $round => $projects) {
            foreach ($projects as $project) {
                foreach ($project['scores'] as $reviewer => $score) {
                    if (!isset($ignoresPerReviewerPerRound[$reviewer][$round])) {
                        $ignoresPerReviewerPerRound[$reviewer][$round] = 0;
                    }
                    if ($score < 0) { // Count ignored reviewer
                        $ignoresPerReviewerPerRound[$reviewer][$round]++;
                    }
                }
            }
        }

        // Calculate the ignore standard deviation for each reviewer
        foreach ($ignoresPerReviewerPerRound as $reviewer => $roundIgnores) {
            $numberOfIgnores = array_sum($roundIgnores);

            // No need to calculate when there are no ignores in any round as the std deviation would be 0
            if ($numberOfIgnores > 0) {
                $averageIgnoresPerRound = ($numberOfIgnores / $rounds);
                // Get the frequencies for the number of ignores per round
                $frequencies = [];
                foreach ($roundIgnores as $ignores) {
                    if (!isset($frequencies[$ignores])) {
                        $frequencies[$ignores] = 0;
                    }
                    $frequencies[$ignores]++;
                }
                $totalSquare = 0;
                // Calculate sum of: [frequency] * [distance_to_average]^2
                foreach ($frequencies as $ignores => $frequency) {
                    $totalSquare += ($frequency * (($ignores - $averageIgnoresPerRound) ** 2));
                }
                $standardDeviation = sqrt(($totalSquare / array_sum($frequencies)));
                $ignoreStandardDeviationSum += $standardDeviation;
            }
        }

        return (float)$ignoreStandardDeviationSum;
    }

    /**
     * Generate PO or FPP roster from group assignments
     *
     * @param array $groupAssignments
     * @param array $reviewerData
     *
     * @return array
     */
    private function generatePoFppPprRosterData(array $groupAssignments, array $reviewerData): array
    {
        $reviewTeams = []; // Keep a list of created reviewer teams to keep teams mixed
        $handlesAssigned = []; // Keep a list of assigned handles per round
        $numberOfReviewTeamRepeat = floor((self::TEAM_RECURRENCE_FACTOR * count($groupAssignments)));

        // Init roster data based on the group assignments array wiping out all boosts keeping the ignores
        $rosterData = $groupAssignments;
        foreach ($rosterData as &$rosterProjects) {
            foreach ($rosterProjects as &$rosterProjectData) {
                foreach ($rosterProjectData['scores'] as &$rosterScore) {
                    if ($rosterScore > 0) {
                        $rosterScore = self::REVIEWER_UNASSIGNED;
                    }
                }
            }
        }

        foreach ($groupAssignments as $round => $projects) {
            $handlesAssigned[$round] = [];

            // Determine the best project match based on score for each reviewer
            $bestMatchByHandle = [];
            $bestMatchesByProject = [];
            foreach ($projects as $projectIndex => $projectData) {
                // When there are FE reviewers, we just need to assign 1 reviewer based on FE score boost.
                // For the other FE reviewers the FE score boost can he undone and they should be assigned based on
                // regular review history score.
                $lastHistoryItem = end($projectData['data']['history']);
                if ($lastHistoryItem && isset($lastHistoryItem[ReviewerService::TYPE_FE])) {
                    $highestScore = 0;
                    $bestMatch = null;
                    $feHandles = $lastHistoryItem[ReviewerService::TYPE_FE];
                    shuffle($feHandles); // Add some randomization when FE reviewers have equal scores
                    foreach ($feHandles as $handle) {
                        // The FE handle could have been overruled by the ignored list
                        if (isset($projectData['scores'][$handle])) {
                            if ($projectData['scores'][$handle] > $highestScore) {
                                $highestScore = $projectData['scores'][$handle];
                                $bestMatch = $handle;
                            }
                        }
                    }
                    // Undo the FE boost for the reviewers that weren't the best match
                    foreach ($feHandles as $handle) {
                        if (isset($projectData['scores'][$handle]) && ($handle !== $bestMatch)) {
                            $projectData['scores'][$handle] -= (self::$scoreBoost[ReviewerService::TYPE_FE]
                                / $this->avgReviewActivityScore);
                        }
                    }
                }

                // Determine the best matches per project and per handle
                $bestMatchesByProjectTemp = [$projectIndex => []];
                $bestMatchesByProject[$projectIndex] = [];
                foreach ($projectData['scores'] as $handle => $score) {
                    if ($score > 0) {
                        // Score is not set yet or higher
                        if (!isset($bestMatchByHandle[$handle]) || ($score > $bestMatchByHandle[$handle]['score'])) {
                            $bestMatchByHandle[$handle] = [
                                'projectIndex' => $projectIndex,
                                'score' => $score
                            ];
                        }
                        $bestMatchesByProjectTemp[$projectIndex][(string)$score][] = [
                            'handle' => $handle,
                            'score' => $score
                        ];
                    }
                }
                // Order from highest score to lowest
                krsort($bestMatchesByProjectTemp[$projectIndex]);
                // We just need the highest scoring handles in a descending list
                foreach ($bestMatchesByProjectTemp[$projectIndex] as $handlesPerScore) {
                    foreach ($handlesPerScore as $handleData) {
                        // Exclude spare reviewers from best matches when required
                        if ($this->includeSpareReviewers || $reviewerData[$handleData['handle']]['present']) {
                            $bestMatchesByProject[$projectIndex][] = $handleData;
                        }
                    }
                }
            }

            foreach ($projects as $projectIndex => $projectData) {
                $reviewersAssigned = [];
                $hasExperiencedReviewer = false;
                $hasSpareReviewer = false;
                $hasEnoughPresentReviewers = $this->includeSpareReviewers;
                $excludeFromProject = []; // Replace these reviewers for better mixing of review teams

                // Add the highest scoring matches per project
                if (isset($bestMatchesByProject[$projectIndex])) {
                    // Only check for mixing of teams when there are actual teams
                    if (($this->reviewersPerProject > 1)) {
                        $consideredHandles = array_slice(
                            $bestMatchesByProject[$projectIndex],
                            0,
                            $this->reviewersPerProject
                        );
                        $teamKey = '';
                        foreach ($consideredHandles as $handleData) {
                            $teamKey .= $handleData['handle'] . '|';
                        }
                        if (!empty($teamKey)) {
                            if (!isset($reviewTeams[$teamKey])) {
                                $reviewTeams[$teamKey] = 0;
                            }
                            $reviewTeams[$teamKey]++;
                            // These reviewers have been grouped together too often, bring some variation
                            if ($reviewTeams[$teamKey] > $numberOfReviewTeamRepeat) {
                                $startIndex = 0;
                                // Check if there is a difference in reviewer scores.
                                // If there is, don't exclude the highest scoring reviewer!
                                $highestScore = $consideredHandles[0]['score'];
                                foreach ($consideredHandles as $handleData) {
                                    if ($handleData['score'] < $highestScore) {
                                        $startIndex = 1;
                                        break;
                                    }
                                }
                                $index = rand($startIndex, ($this->reviewersPerProject - 1));
                                if (isset($bestMatchesByProject[$projectIndex][$index])) {
                                    $handle = $bestMatchesByProject[$projectIndex][$index]['handle'];
                                    $excludeFromProject[] = $handle;
                                    // When excluded from this project, also remove this project from the best matches by handle
                                    if (isset($bestMatchByHandle[$handle])
                                        && ($bestMatchByHandle[$handle]['projectIndex'] === $projectIndex)
                                    ) {
                                        unset($bestMatchByHandle[$handle]);
                                    }
                                }
                            }
                        }
                    }

                    foreach ($bestMatchesByProject[$projectIndex] as $handleData) {
                        $handle = $handleData['handle'];
                        if (// Not explicitly excluded
                            !in_array($handle, $excludeFromProject)
                            // Not yet assigned in this round
                            && !in_array($handle, $handlesAssigned[$round])
                            // Not a best match on another project, or the other project has already been assigned to other reviewers
                            && (!isset($bestMatchByHandle[$handle])
                                || ($bestMatchByHandle[$handle]['projectIndex'] <= $projectIndex))
                            // Don't assign multiple spare reviewers
                            && (!$hasSpareReviewer || $reviewerData[$handle]['present'])
                            // Don't add reviewers from the same organisation
                            && !$this->sameOrganisation($handle, $reviewersAssigned, $reviewerData)
                        ) {
                            $assigned = $reviewerData[$handle]['present'] ? self::REVIEWER_ASSIGNED
                                : self::REVIEWER_SPARE;
                            $rosterData[$round][$projectIndex]['scores'][$handle] = $assigned;
                            $reviewersAssigned[] = $handle;
                            $handlesAssigned[$round][] = $handle;
                            if ($reviewerData[$handle]['experienced']) {
                                $hasExperiencedReviewer = true;
                            }
                            if (!$reviewerData[$handle]['present']) {
                                $hasSpareReviewer = true;
                            }
                        }
                        // Already enough reviewers, no need to go further
                        if ((count($reviewersAssigned) === $this->reviewersPerProject)) {
                            $hasEnoughPresentReviewers = true;
                            break;
                        }
                    }
                }

                // All done here, on to the next project
                if (count($reviewersAssigned) === $this->reviewersPerProject) {
                    continue;
                }

                // Shuffle the reviewer list to equalize assignment chances
                $shuffledProjectData = [];
                $handles = array_keys($projectData['scores']);
                shuffle($handles);
                foreach ($handles as $handle) {
                    $shuffledProjectData[$handle] = $projectData['scores'][$handle];
                }

                // Fill with other reviewers
                foreach ($shuffledProjectData as $handle => $score) {
                    // Enough reviewers, no need to go further
                    if (count($reviewersAssigned) === $this->reviewersPerProject) {
                        break;
                    }
                    if (// Not explicitly excluded
                        ($score > self::REVIEWER_IGNORED) && !in_array($handle, $excludeFromProject)
                        // Not yet assigned in this round
                        && !in_array($handle, $handlesAssigned[$round])
                        // Not a best match on another project, or the other project has already been assigned to other reviewers
                        && (!isset($bestMatchByHandle[$handle])
                            || ($bestMatchByHandle[$handle]['projectIndex'] <= $projectIndex))
                        // Don't assign multiple spare reviewers
                        && (!$hasSpareReviewer || $reviewerData[$handle]['present'])
                        // Prefer present reviewers when spare reviewers aren't counted
                        && ($hasEnoughPresentReviewers || $reviewerData[$handle]['present'])
                        // Don't add reviewers from the same organisation
                        && !$this->sameOrganisation($handle, $reviewersAssigned, $reviewerData)
                    ) {
                        // When no experienced reviewer has been assigned yet, skip inexperienced reviewers
                        if ((count($reviewersAssigned) === ($this->reviewersPerProject - 1))
                            && !$hasExperiencedReviewer
                            && !$reviewerData[$handle]['experienced']
                        ) {
                            continue;
                        }
                        // Assign the reviewer
                        $assigned = $reviewerData[$handle]['present'] ? self::REVIEWER_ASSIGNED : self::REVIEWER_SPARE;
                        $rosterData[$round][$projectIndex]['scores'][$handle] = $assigned;
                        $reviewersAssigned[] = $handle;
                        $handlesAssigned[$round][] = $handle;
                        if ($reviewerData[$handle]['experienced']) {
                            $hasExperiencedReviewer = true;
                        }
                        if (!$reviewerData[$handle]['present']) {
                            $hasSpareReviewer = true;
                        }
                    }
                }
            }
        }

        // Assign unassigned reviewers to best matching projects
        $this->assignUnassignedReviewers($rosterData, $groupAssignments, $reviewerData);

        return $rosterData;
    }

    /**
     * Assign unassigned reviewers as extra reviewers for projects in a review round or fill up edge cases when too few
     * reviewers have been assigned
     *
     * @param array $rosterData
     * @param array $groupAssignments
     * @param array $reviewerData
     */
    private function assignUnassignedReviewers(array &$rosterData, array $groupAssignments, array $reviewerData): void
    {
        $allReviewers = array_keys($reviewerData);
        $lastRound = count($rosterData);

        foreach ($rosterData as $round => $projects) {
            $assignedReviewers = [];
            $projectsWithInsufficientReviewers = [];
            foreach ($projects as $projectIndex => $project) {
                $assignedReviewersPerProject = 0;
                foreach ($project['scores'] as $handle => $score) {
                    if (in_array($score, self::$assigned)) {
                        $assignedReviewers[] = $handle;
                        $assignedReviewersPerProject++;
                    }
                }
                // Edge case when not enough reviewers have been assigned by the previous steps
                if ($assignedReviewersPerProject < $this->reviewersPerProject) {
                    $projectsWithInsufficientReviewers[$projectIndex] = $assignedReviewersPerProject;
                }
            }
            $unassignedReviewers = array_diff($allReviewers, $assignedReviewers);

            // Add reviewers to projects with insufficient reviewers
            foreach ($projectsWithInsufficientReviewers as $projectIndex => $assignedReviewersPerProject) {
                for ($counter = $assignedReviewersPerProject; ($counter < $this->reviewersPerProject); $counter++) {
                    // No more reviewers left to assign, break out of this section
                    if (empty($unassignedReviewers)) {
                        break 2;
                    }
                    $handle = reset($unassignedReviewers);
                    if ($rosterData[$round][$projectIndex]['scores'][$handle] !== self::REVIEWER_IGNORED) {
                        $assignedType = $reviewerData[$handle]['present'] ? self::REVIEWER_ASSIGNED
                            : self::REVIEWER_SPARE;
                        $rosterData[$round][$projectIndex]['scores'][$handle] = $assignedType;
                        array_shift($unassignedReviewers);
                    }
                }
            }

            // No extra assignments for the last round
            if (!empty($unassignedReviewers) && ($round < $lastRound)) {
                // Assign leftover unassigned reviewers to the other projects based on number of assignments and score
                while (!empty($unassignedReviewers)) {
                    shuffle($unassignedReviewers);
                    $handle = reset($unassignedReviewers);
                    $sortedProjectIndexes = $this->getLeastAssignmentsProjectIndex($rosterData[$round]);
                    $bestMatchIndex = null;
                    $bestScore = self::REVIEWER_IGNORED;
                    // Iterate the projects with the least assignments and get the best matching project
                    foreach ($sortedProjectIndexes as $projectIndexes) {
                        foreach ($projectIndexes as $projectIndex) {
                            $score = $groupAssignments[$round][$projectIndex]['scores'][$handle];
                            if ($score > $bestScore) {
                                $bestMatchIndex = $projectIndex;
                                $bestScore = $score;
                            }
                        }
                        // Break out as soon as a best match has been found. Iterating further will only get less good
                        // matches, but is sometimes needed when there are a lot of blocked assignments
                        if ($bestMatchIndex !== null) {
                            break;
                        }
                    }

                    $assignedType = $reviewerData[$handle]['present'] ? self::REVIEWER_EXTRA
                        : self::REVIEWER_EXTRA_SPARE;
                    $rosterData[$round][$bestMatchIndex]['scores'][$handle] = $assignedType;
                    array_shift($unassignedReviewers);
                }
            }
        }
    }

    /**
     * Get the project(s) with the least assignments in a round
     *
     * @param array $rosterDataRound
     *
     * @return array
     */
    private function getLeastAssignmentsProjectIndex(array $rosterDataRound): array
    {
        $projectAssignments = [];
        foreach ($rosterDataRound as $projectIndex => $data) {
            $projectAssignmentCount = 0;
            foreach ($data['scores'] as $score) {
                if (in_array($score, self::$assigned)) {
                    $projectAssignmentCount++;
                }
            }
            if (!isset($projectAssignments[$projectAssignmentCount])) {
                $projectAssignments[$projectAssignmentCount] = [];
            }
            // Allow for multiple projects with the same number of assignments
            $projectAssignments[$projectAssignmentCount][] = $projectIndex;
            shuffle($projectAssignments[$projectAssignmentCount]);
        }
        ksort($projectAssignments);

        return $projectAssignments;
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
                    $this->log(
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
        $this->log(__LINE__, 'Roster test completed successfully.');

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
                                'name' => $reviewContact->getContact()->parseFullName(),
                                'organisation' => $organisation->getOrganisation(),
                                'parent' => $parent,
                                'present' => ($sheetIndex === 0),
                                'risky' => ((int)$sheet->getCell('B' . $row)->getValue() === 1),
                                'experienced' => ((int)$sheet->getCell('C' . $row)->getValue() === 1),
                                'weight' => (float)$sheet->getCell('D' . $row)->getValue(),
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
        return $this->log;
    }
}
