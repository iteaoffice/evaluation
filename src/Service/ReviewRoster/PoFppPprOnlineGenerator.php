<?php

declare(strict_types=1);

namespace Evaluation\Service\ReviewRoster;

use Evaluation\Service\ReviewerService;

use function array_values;
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

        // Assign reviewers
        foreach ($assignments as $projectIndex => &$assignment) {
            $this->reviewersAssigned = [];
            // If future evaluation is set, get reviewers from there
            $lastHistoryItem = end($this->projectReviewerScores[$projectIndex]['data']['history']);
            if ($lastHistoryItem && array_key_exists(ReviewerService::TYPE_FE, $lastHistoryItem)) {
                $this->assignFutureEvaluationReviewers($assignment, $projectIndex);
            }
            // Otherwise, add the highest scoring match per project
            elseif (!empty($bestMatchesByProject[$projectIndex])) {
                $this->assignBestProjectMatch($assignment, $bestMatchesByProject[$projectIndex]);
            }

            // Add randomly picked other reviewers based on reviewer workload
            $this->assignRandomReviewers($assignment);
        }
        //die();

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
}
