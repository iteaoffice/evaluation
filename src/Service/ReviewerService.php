<?php
/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @category    Project
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2017 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\Service;

use Affiliation\Service\AffiliationService;
use Calendar\Entity\Contact as CalendarContact;
use Calendar\Entity\ContactRole;
use Calendar\Entity\ContactStatus;
use Contact\Entity\Contact;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use General\Entity\Country;
use Program\Entity\Call\Call;
use Project\Entity\Calendar\Calendar as ProjectCalendar;
use Project\Entity\Calendar\Review as CalendarReview;
use Project\Entity\Project;
use Project\Entity\Report\Report as ProjectReport;
use Evaluation\Entity\Reviewer\Contact as ReviewContact;
use Evaluation\Entity\Reviewer as ProjectReviewer;
use Project\Entity\Version\Reviewer as VersionReviewer;
use Project\Entity\Version\Version;
use Evaluation\Repository\Reviewer\ContactRepository as ReviewContactRepository;
use function count;
use function implode;
use function in_array;
use function ksort;
use function preg_replace;
use function sprintf;
use function strtoupper;

/**
 * Class ReviewerService
 * @package Evaluation\Service
 */
class ReviewerService extends AbstractService
{
    public const TYPE_PO  = 'PO';  // Project outline
    public const TYPE_FPP = 'FPP'; // Full project proposal
    public const TYPE_CR  = 'CR';  // Change request
    public const TYPE_PPR = 'PPR'; // Project progress report
    public const TYPE_R   = 'R';   // Project review
    public const TYPE_FE  = 'FE';  // Future evaluation
    public const TYPE_PR  = 'PR';  // Preferred reviewers
    public const TYPE_IGN = 'IGN'; // Ignored reviewers

    /**
     * @var AffiliationService
     */
    private $affiliationService;

    public function __construct(EntityManager $entityManager, AffiliationService $affiliationService)
    {
        parent::__construct($entityManager);
        $this->affiliationService = $affiliationService;
    }

    /**
     * @param Project $project
     *
     * @return string
     * @deprecated No longer used, will be removed at some point
     */
    public function exportReviewers(Project $project): string
    {
        $lineBreak = "\r\n";
        $counter = 0;
        $startDate = '-1';

        if (null !== $project->getDateStartActual()) {
            /** @var DateTime $actualStartDate */
            $actualStartDate = $project->getDateStartActual();
            $startDate = $actualStartDate->format('y') . "\t"
                . $actualStartDate->format('m') . "\t"
                . $actualStartDate->format('d');
        }

        /**
         * Parse a reviewers row
         *
         * @param array  $reviewers
         * @param string $type
         *
         * @return string
         */
        $parseRow = static function (array $reviewers, string $type) use ($lineBreak): string {
            $reviewerCount = count($reviewers);
            // Skip empty FE lines
            if (($type === self::TYPE_FE) && ($reviewerCount === 0)) {
                return '';
            }
            $return = "\t" . $type . "\t" . $reviewerCount;
            if ($reviewerCount > 0) {
                $return .= "\t" . implode("\t", $reviewers);
            }

            return $return . $lineBreak;
        };

        // Get general project reviewers
        $projectReviewers = [];

        // Get the preferred reviewers
        $projectReviewers[self::TYPE_PR] = $this->getPreferredReviewers($project);

        // Get the ignored reviewers dynamically from project partners
        $projectReviewers[self::TYPE_IGN] = $this->getIgnoredReviewers($project);

        // Get the complete chronologically sorted review history for the project
        $reviewHistory = $this->getReviewHistory($project);
        $counter += count($reviewHistory);

        // Get the active partner countries
        $countries = [];
        $countriesTemp = $this->affiliationService->findAffiliationCountriesByProjectAndWhich(
            $project,
            AffiliationService::WHICH_ONLY_ACTIVE
        );
        /** @var Country $country */
        foreach ($countriesTemp as $country) {
            $countries[] = $country->getIso3();
        }
        $countryCount = count($countries);

        // Get the call number (counting from ITEA 2 Call 1)
        $callCollection = $this->entityManager->getRepository(Call::class)->findAll();
        $callCounter = 1;
        /** @var Call $call */
        foreach ($callCollection as $call) {
            if ($call->getProgram()->getId() > 1) { // Skip ITEA 1
                if ($call->getId() === $project->getCall()->getId()) {
                    break;
                } else {
                    $callCounter++;
                }
            }
        }

        $fullProjectName = empty($project->getTitle())
            ? 'not_available'
            : preg_replace('/\s+/', '_', $project->getTitle());

        // Build the output
        $return = preg_replace('/\s+/', '_', $project->getProject()) . $lineBreak;
        $return .= "\tfullname\t" . $fullProjectName . $lineBreak;
        $return .= "\tcountries\t" . $countryCount . (($countryCount > 0) ? "\t" . implode("\t", $countries) : '')
            . $lineBreak;
        $return .= "\tcall\t" . $callCounter . $lineBreak;
        $return .= "\tstart\t" . $startDate . $lineBreak;
        $return .= "\tweight\t1" . $lineBreak;
        $return .= "\t" . $counter . $lineBreak;

        // Add review history
        foreach ($reviewHistory as $reviewLine) {
            foreach ($reviewLine as $type => $reviewers) {
                $return .= $parseRow($reviewers, $type);
            }
        }

        // Project reviewers PR / IGN
        foreach ($projectReviewers as $type => $reviewers) {
            $return .= $parseRow($reviewers, $type);
        }

        return $return;
    }

    /**
     * Get the preferred reviewers for a project
     *
     * @param Project $project
     *
     * @return array
     */
    public function getPreferredReviewers(Project $project): array
    {
        $preferredReviewers = [];
        foreach ($project->getReviewers() as $projectReviewer) {
            $type = strtoupper($projectReviewer->getType()->getType());
            if ($type === self::TYPE_PR) {
                $preferredReviewers[] = $this->getReviewHandle($projectReviewer->getContact(), $project);
            }
        }
        return $preferredReviewers;
    }

    /**
     * Get the reviewer handle for a contact. Throws an error when no handle has been defined yet.
     *
     * @param Contact $contact
     * @param Project $project
     *
     * @return string
     * @throws Exception
     * @see https://ticket.itea3.org/youtrack/issue/PROJECT-3707
     */
    private function getReviewHandle(Contact $contact, Project $project): string
    {
        if ($contact->getProjectReviewerContact() === null) {
            throw new Exception(
                sprintf(
                    'Unable to get the project review handle for %s. Has one been defined? - Project: %s',
                    $contact->parseFullName(),
                    $project->parseFullName()
                )
            );
        }
        return $contact->getProjectReviewerContact()->getHandle();
    }

    /**
     * Get the ignored reviewers for a project
     *
     * @param Project $project
     *
     * @return array
     */
    public function getIgnoredReviewers(Project $project): array
    {
        $preferredReviewers = [];
        /** @var ReviewContactRepository $repository */
        $repository = $this->entityManager->getRepository(ReviewContact::class);
        foreach ($repository->findIgnoredReviewers($project) as $reviewer) {
            $preferredReviewers[] = $reviewer->getHandle();
        }
        return $preferredReviewers;
    }

    public function getReviewHistory(Project $project): array
    {
        // Get project version reviewers
        $hasFE = false;
        $projectVersionReviewers = [];
        /** @var Version $version */
        foreach ($project->getVersion() as $version) {
            $type = strtoupper($version->getVersionType()->getType());
            $sortKey = $version->getDateSubmitted()->format('Y-m-d|H:i:s') . '|' . $type;
            $projectVersionReviewers[$sortKey][$type] = [];
            /** @var VersionReviewer $versionReview */
            foreach ($version->getReviewers() as $versionReview) {
                $projectVersionReviewers[$sortKey][$type][]
                    = $this->getReviewHandle($versionReview->getContact(), $project);
            }
        }

        // Get project calendar and future evaluation reviewers
        $projectCalendarReviewers = [];
        $tomorrow = new DateTime('tomorrow');
        $stgRoles = [ContactRole::ROLE_STG_REVIEWER, /*ContactRole::ROLE_STG_SPARE_REVIEWER*/];
        /** @var ProjectCalendar $projectCalendar */
        foreach ($project->getProjectCalendar() as $projectCalendar) {
            // Add the FE reviewers for calendar items in the future, limiting to max 1 FE line
            if (!$hasFE && ($projectCalendar->getCalendar()->getDateFrom() >= $tomorrow)) {
                $sortKey = $projectCalendar->getCalendar()->getDateFrom()->format('Y-m-d|H:i:s')
                    . '|' . self::TYPE_FE;
                /** @var CalendarContact $attendee */
                foreach ($projectCalendar->getCalendar()->getCalendarContact() as $attendee) {
                    // Include steering group reviewers (spare reviewers disabled now)
                    if (in_array($attendee->getRole()->getId(), $stgRoles)) {
                        $projectCalendarReviewers[$sortKey][self::TYPE_FE][]
                            = $this->getReviewHandle($attendee->getContact(), $project);
                        $hasFE = true;
                    }
                }
            }
            // Add regular reviewers for past calendar items
            else {
                $sortKey = $projectCalendar->getCalendar()->getDateFrom()->format('Y-m-d|H:i:s')
                    . '|' . self::TYPE_R;
                $projectCalendarReviewers[$sortKey][self::TYPE_R] = [];
                // Add the legacy Calendar\Review items from the old reviewer import
                /** @var CalendarReview $calendarReview */
                foreach ($projectCalendar->getReview() as $calendarReview) {
                    $projectCalendarReviewers[$sortKey][self::TYPE_R][]
                        = $this->getReviewHandle($calendarReview->getContact(), $project);
                }
                // Merge data above with actual data from the calendar items and add reviewers from more recent meetings
                foreach ($projectCalendar->getCalendar()->getCalendarContact() as $attendee) {
                    // The attendee is a project reviewer and status = accepted (assume that reviewer was present)
                    if (in_array($attendee->getRole()->getId(), $stgRoles)
                        && ($attendee->getStatus() === ContactStatus::STATUS_ACCEPT)
                    ) {
                        $handle = $this->getReviewHandle($attendee->getContact(), $project);
                        // Only add when not already added from historical data
                        if (!in_array($handle, $projectCalendarReviewers[$sortKey][self::TYPE_R])) {
                            $projectCalendarReviewers[$sortKey][self::TYPE_R][] = $handle;
                        }
                    }
                }
            }
        }

        // Get project report reviewers
        $projectReportReviewers = [];
        /** @var ProjectReport $projectReport */
        foreach ($project->getReport() as $projectReport) {
            $sortKey = $projectReport->getDateCreated()->format('Y-m-d|H:i:s') . '|' . self::TYPE_PPR;
            $projectReportReviewers[$sortKey][self::TYPE_PPR] = [];
            /** @var ProjectReviewer $projectReview */
            foreach ($projectReport->getReviewers() as $reportReview) {
                $projectReportReviewers[$sortKey][self::TYPE_PPR][]
                    = $this->getReviewHandle($reportReview->getContact(), $project);
            }
        }

        // Combine and sort the review arrays chronologically
        $allReviewers = $projectVersionReviewers + $projectCalendarReviewers + $projectReportReviewers;
        ksort($allReviewers);

        return $allReviewers;
    }
}
