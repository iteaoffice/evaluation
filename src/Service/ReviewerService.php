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

use Calendar\Entity\Contact as CalendarContact;
use Calendar\Entity\ContactRole;
use Calendar\Entity\ContactStatus;
use Contact\Entity\Contact;
use DateTime;
use InvalidArgumentException;
use Project\Entity\Calendar\Calendar as ProjectCalendar;
use Project\Entity\Calendar\Reviewer as CalendarReviewer;
use Project\Entity\Project;
use Project\Entity\Report\Report as ProjectReport;
use Evaluation\Entity\Reviewer\Contact as ReviewContact;
use Evaluation\Entity\Reviewer as ProjectReviewer;
use Project\Entity\Version\Reviewer as VersionReviewer;
use Project\Entity\Version\Version;
use Evaluation\Repository\Reviewer\ContactRepository as ReviewContactRepository;
use function in_array;
use function ksort;
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

    /*
     * Get the reviewer handle for a contact. Throws an error when no handle has been defined yet.
     * @see https://ticket.itea3.org/youtrack/issue/PROJECT-3707
     */
    private function parseReviewHandle(Contact $contact): string
    {
        if ($contact->getProjectReviewerContact() === null) {
            throw new InvalidArgumentException(sprintf(
                'Unable to get the project review handle for %s. Has one been defined?',
                $contact->parseFullName()
            ));
        }
        return $contact->getProjectReviewerContact()->getHandle();
    }

    public function getPreferredReviewers(Project $project): array
    {
        $preferredReviewers = [];
        foreach ($project->getReviewers() as $projectReviewer) {
            $type = strtoupper($projectReviewer->getType()->getType());
            if ($type === self::TYPE_PR) {
                $preferredReviewers[] = $this->parseReviewHandle($projectReviewer->getContact());
            }
        }
        return $preferredReviewers;
    }

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
                    = $this->parseReviewHandle($versionReview->getContact());
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
                            = $this->parseReviewHandle($attendee->getContact());
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
                /** @var CalendarReviewer $calendarReview */
                /*foreach ($projectCalendar->getReviewers() as $calendarReview) {
                    $projectCalendarReviewers[$sortKey][self::TYPE_R][]
                        = $this->parseReviewHandle($calendarReview->getContact());
                }*/
                // Merge data above with actual data from the calendar items and add reviewers from more recent meetings
                foreach ($projectCalendar->getCalendar()->getCalendarContact() as $attendee) {
                    // The attendee is a project reviewer and status = accepted (assume that reviewer was present)
                    if (in_array($attendee->getRole()->getId(), $stgRoles)
                        && ($attendee->getStatus()->getId() === ContactStatus::STATUS_ACCEPT)
                    ) {
                        $handle = $this->parseReviewHandle($attendee->getContact());
                        // Only add when not already added from historical data
                        //if (!in_array($handle, $projectCalendarReviewers[$sortKey][self::TYPE_R])) {
                        $projectCalendarReviewers[$sortKey][self::TYPE_R][] = $handle;
                        //}
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
                    = $this->parseReviewHandle($reportReview->getContact());
            }
        }

        // Combine and sort the review arrays chronologically
        $allReviewers = $projectVersionReviewers + $projectCalendarReviewers + $projectReportReviewers;
        ksort($allReviewers);

        return $allReviewers;
    }
}
