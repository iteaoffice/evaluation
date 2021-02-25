<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Service\ReviewRoster;

use Evaluation\Service\ReviewerService;

use Evaluation\Service\ReviewRosterService;
use function array_values;
use function arsort;
use function asort;
use function ksort;

final class PoFppPprOnlineGenerator extends CrGenerator
{
    public function generate(): RosterData
    {
        $this->logger->reset();
        $this->initReviewerLoad();
        // Init assignment data based on the project reviewer scores array wiping out all boosts keeping the ignores
        $assignments = $this->initProjectAssignments();
        // Determine the best matches per project
        $bestMatchesByProject = $this->getBestMatchesPerProject();
        $sortedProjectScores = $this->getSortedProjectScores();

        // Assign reviewers
        foreach ($assignments as $projectIndex => &$assignment) {
            $this->reviewersAssigned = [];
            // If future evaluation is set, get reviewers from there
            $lastHistoryItem = end($this->projectReviewerScores[$projectIndex]['data']['history']);
            if ($lastHistoryItem && array_key_exists(ReviewerService::TYPE_FE, $lastHistoryItem)) {
                $this->assignFutureEvaluationReviewers($assignment, $projectIndex);
            }
            // Otherwise, add the highest scoring match per project
            elseif (count($bestMatchesByProject[$projectIndex]) > 0) {
                $this->assignBestProjectMatch($assignment, $bestMatchesByProject[$projectIndex]);
            }

            // Add other reviewers based on reviewer history + workload
            $this->assignOtherReviewers($assignment, $sortedProjectScores[$projectIndex]);
        }

        return new RosterData([$this->sortAssignments($assignments)], $this->logger);
    }

    // Sort by project number
    private function sortAssignments(array $projectAssignments): array
    {
        $projectAssignmentsSorted = [];
        foreach ($projectAssignments as $projectAssignment) {
            $projectAssignmentsSorted[$projectAssignment['data']['number']] = $projectAssignment;
        }
        ksort($projectAssignmentsSorted);

        return array_values($projectAssignmentsSorted);
    }

    private function assignOtherReviewers(array &$assignment, array $sortedProjectScores): void
    {
        $iteration = 1; // Fail safe to prevent infinite loop
        asort($this->reviewerLoad);
        $reviewers = count($this->reviewerData);
        while ((count($this->reviewersAssigned) < $this->reviewersPerProject) && ($iteration <= $reviewers)) {
            $this->assignBestMatch($assignment, $sortedProjectScores);
        }
    }

    private function getSortedProjectScores(): array
    {
        static $sortedProjectScores = null;

        if ($sortedProjectScores === null) {
            $sortedProjectScores = [];
            foreach ($this->projectReviewerScores as $projectIndex => $project) {
                $sortedScores = $project['scores'];
                arsort($sortedScores);
                $sortedProjectScores[$projectIndex] = $sortedScores;
            }
        }

        return $sortedProjectScores;
    }

    private function assignBestMatch(array &$assignment, array $sortedProjectScores): void
    {
        // Re-calculate and sort score as reviewer load has changed
        $projectCount = count($this->projectReviewerScores);
        foreach ($sortedProjectScores as $handle => $score) {
            $sortedProjectScores[$handle] = ($projectCount - $this->reviewerLoad[$handle]) * $score;
        }
        arsort($sortedProjectScores);

        foreach (array_keys($sortedProjectScores) as $handle) {
            // Add reviewers with low load and not from the same organisation as reviewers already assigned
            if (
                ($assignment['scores'][$handle] === 0)
                && !$this->sameOrganisation($handle, $this->reviewersAssigned, $this->reviewerData)
            ) {
                $this->reviewersAssigned[] = $handle;
                $this->reviewerLoad[$handle]++;
                $assignment['scores'][$handle] = ReviewRosterService::REVIEWER_ASSIGNED;
                $this->logger->log(
                    __LINE__,
                    sprintf(
                        '%s: %s assigned based on reviewer history and workload',
                        $assignment['data']['number'] . ' ' . $assignment['data']['name'],
                        $handle
                    )
                );
                break 1;
            }
        }
    }
}
