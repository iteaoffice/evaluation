<?php

declare(strict_types=1);

namespace Evaluation\Service\ReviewRoster;

use Evaluation\Service\ReviewerService;
use Evaluation\Service\ReviewRosterService;

class CrGenerator extends AbstractGenerator
{
    protected array $reviewerLoad;

    public function __construct(array $config, array $projectReviewerScores, int $reviewersPerProject)
    {
        parent::__construct($config, $projectReviewerScores, $reviewersPerProject);
        // Init reviewer load
        foreach (array_keys($this->reviewerData) as $handle) {
            $this->reviewerLoad[$handle] = 0;
        }
    }

    public function generate(): RosterData
    {
        $logger = new Logger();
        $assignments = $this->initAssignments();
        $bestMatchesByProject = $this->getBestMatchesPerProject();

        // Assign reviewers
        foreach ($assignments as $projectIndex => &$assignment) {
            $reviewersAssigned = [];
            $hasPrimaryReviewer = false;

            // If future evaluation is set, get reviewers from there
            $lastHistoryItem = end($this->projectReviewerScores[$projectIndex]['data']['history']);
            if ($lastHistoryItem && isset($lastHistoryItem[ReviewerService::TYPE_FE])) {
                foreach ($lastHistoryItem[ReviewerService::TYPE_FE] as $handle) {
                    if (count($reviewersAssigned) === $this->reviewersPerProject) {
                        break;
                    }
                    if (
                        isset($reviewerData[$handle])
                        // Prevent rare cases where a preferred reviewer is also an ignored one (after org merge)
                        && ($this->projectReviewerScores[$projectIndex]['scores'][$handle] !== ReviewRosterService::REVIEWER_IGNORED)
                        // Prevent reviewers from the same company being added
                        && !$this->sameOrganisation($handle, $reviewersAssigned, $this->reviewerData)
                    ) {
                        $reviewersAssigned[] = $handle;
                        $this->reviewerLoad[$handle]++;
                        $assignment['scores'][$handle] = ReviewRosterService::REVIEWER_ASSIGNED;
                        $logger->log(
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
                $this->reviewerLoad[$handle]++;
                $assignment['scores'][$handle] = ReviewRosterService::REVIEWER_ASSIGNED;
                $logger->log(
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
                $assignment['scores'][$handle] = ReviewRosterService::REVIEWER_PRIMARY;
                $hasPrimaryReviewer = true;
                $logger->log(
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
            asort($this->reviewerLoad);
            $reviewers = count($this->reviewerData);
            while ((count($reviewersAssigned) < $this->reviewersPerProject) && ($iteration <= $reviewers)) {
                foreach (array_keys($this->reviewerLoad) as $handle) {
                    // Add reviewers with low load and not from the same organisation as reviewers already assigned
                    if (
                        ($assignment['scores'][$handle] === 0)
                        && !$this->sameOrganisation($handle, $reviewersAssigned, $this->reviewerData)
                    ) {
                        $reviewersAssigned[] = $handle;
                        $this->reviewerLoad[$handle]++;
                        $assignment['scores'][$handle] = ReviewRosterService::REVIEWER_ASSIGNED;
                        asort($this->reviewerLoad);
                        $logger->log(
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
                $assignment['scores'][$handle] = ReviewRosterService::REVIEWER_PRIMARY;
                $logger->log(
                    __LINE__,
                    sprintf(
                        '%s: No primary reviewer based on experience or FE label, randomly assigned %s as primary reviewer',
                        $assignment['data']['number'] . ' ' . $assignment['data']['name'],
                        $handle
                    )
                );
            }
        }

        return new RosterData($this->sortByCall($assignments), $logger);
    }

    // Init assignment data based on the project reviewer scores array wiping out all boosts keeping the ignores
    protected function initAssignments(): array
    {
        $assignments = $this->projectReviewerScores;
        foreach ($assignments as &$assignmentsProjectData) {
            foreach ($assignmentsProjectData['scores'] as &$assignmentScore) {
                if ($assignmentScore > 0) {
                    $assignmentScore = ReviewRosterService::REVIEWER_UNASSIGNED;
                }
            }
        }

        return $assignments;
    }

    // Sort by call DESC
    private function sortByCall(array $assignments): array
    {
        $rosterData = [];
        foreach ($assignments as $assignment) {
            if (!isset($rosterData[$assignment['data']['call']])) {
                $rosterData[$assignment['data']['call']] = [];
            }
            $rosterData[$assignment['data']['call']][] = $assignment;
        }
        krsort($rosterData);

        return $rosterData;
    }
}
