<?php

declare(strict_types=1);

namespace Evaluation\Service\ReviewRoster;

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
            // Add best project match
            if (!empty($bestMatchesByProject[$projectIndex])) {
                $this->assignBestProjectMatch($assignment, $bestMatchesByProject[$projectIndex]);
            }

            // Add randomly picked other reviewers based on reviewer workload
            $this->assignRandomReviewers($assignment);
        }

        return new RosterData([$assignments], $this->logger);
    }
}
