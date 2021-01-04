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

use function array_sum;
use function count;
use function sqrt;

final class RoundOptimizer
{
    private const MAX_ITERATIONS = 10;  // Maximum optimization iterations of the whole roster

    private array $projectsPerRound;

    public function __construct(array $projectsPerRound)
    {
        $this->projectsPerRound = $projectsPerRound;
    }

    // Optimize the distribution of boosts and ignores among the rounds for better reviewer assignment.
    public function optimize(array $roundAssignments): array
    {
        $boostDistributionScore  = $this->calculateDistributionScoreBoosts($roundAssignments);
        $ignoreDistributionScore = $this->calculateDistributionScoreIgnores($roundAssignments);

        $hasImproved = true;
        for ($iteration = 1; (($iteration <= self::MAX_ITERATIONS) && $hasImproved); $iteration++) {
            $boostDistributionScoreNew  = $boostDistributionScore;
            $ignoreDistributionScoreNew = $ignoreDistributionScore;

            // No need to iterate the last round, as there's no next round to swap projects with
            $totalRounds = count($roundAssignments);
            for ($round = 1; $round < $totalRounds; $round++) {
                // Iterate primary projects
                for ($index1 = 0; $index1 < $this->projectsPerRound[$round]; $index1++) {
                    // For each project, iterate the next rounds
                    $project1 = $roundAssignments[$round][$index1];
                    for ($nextRound = $round + 1; $nextRound <= $totalRounds; $nextRound++) {
                        // For each next round, iterate its projects
                        for ($index2 = 0; $index2 < $this->projectsPerRound[$nextRound]; $index2++) {
                            $roundAssignmentsNew = $roundAssignments;
                            $project2            = $roundAssignments[$nextRound][$index2];
                            // Make the project swap
                            $roundAssignmentsNew[$nextRound][$index2] = $project1;
                            $roundAssignmentsNew[$round][$index1]     = $project2;
                            // Get the new scores for the group assignment with the swapped project
                            $boostDistributionScoreTest  = $this->calculateDistributionScoreBoosts($roundAssignmentsNew);
                            $ignoreDistributionScoreTest = $this->calculateDistributionScoreIgnores(
                                $roundAssignmentsNew
                            );

                            // Have the scores improved after the swap?
                            $hasImprovedAfterSwitch = (
                                // At least one value should have improved
                                (($boostDistributionScoreTest <= $boostDistributionScoreNew)
                                    && ($ignoreDistributionScoreTest <= $ignoreDistributionScoreNew))
                                // Only improvement when BOTH values are NOT the same as their previous value
                                && ! (($boostDistributionScoreNew === $boostDistributionScoreTest)
                                    && ($ignoreDistributionScoreNew === $ignoreDistributionScoreTest))
                            );

                            // Swap resulted in neutral or improvement, update the scores and group assignment
                            if ($hasImprovedAfterSwitch) {
                                //$perc1 = (($boostDistributionScoreNew-$boostDistributionScoreTest)/$boostDistributionScoreNew)*100;
                                //$perc2 = (($ignoreDistributionScoreNew-$ignoreDistributionScoreTest)/$ignoreDistributionScoreNew)*100;
                                //echo $boostDistributionScoreTest.' - '.$perc1.'<br>';
                                //echo $ignoreDistributionScoreTest.' - '.$perc2.'<br>';
                                $boostDistributionScoreNew  = $boostDistributionScoreTest;
                                $ignoreDistributionScoreNew = $ignoreDistributionScoreTest;
                                $roundAssignments           = $roundAssignmentsNew;
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
                && ! (($boostDistributionScoreNew === $boostDistributionScore)
                    && ($ignoreDistributionScoreNew === $ignoreDistributionScore))
            );
            if ($hasImproved) {
                $boostDistributionScore  = $boostDistributionScoreNew;
                $ignoreDistributionScore = $ignoreDistributionScoreNew;
            }
        }

        return $roundAssignments;
    }

    // Calculate the sum of the per-reviewer standard deviations for score boosts.
    // This value can be used to determine the distribution rate of boosts in the rounds.
    // The lower the number, the better
    // http://www.wisfaq.nl/pagina.asp?nummer=1754
    private function calculateDistributionScoreBoosts(array $roundAssignments): float
    {
        // Get the average boosts and ignores
        $boostsPerReviewerPerRound = [];
        $rounds                    = count($roundAssignments);
        $boostStandardDeviationSum = 0;

        foreach ($roundAssignments as $round => $projects) {
            foreach ($projects as $project) {
                foreach ($project['scores'] as $reviewer => $score) {
                    if (! isset($boostsPerReviewerPerRound[$reviewer][$round])) {
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
                    if (! isset($frequencies[$boostScore])) {
                        $frequencies[$boostScore] = 0;
                    }
                    $frequencies[$boostScore]++;
                }
                $totalSquare = 0;
                // Calculate sum of: [frequency] * [distance_to_average]^2
                foreach ($frequencies as $boostScore => $frequency) {
                    $totalSquare += ($frequency * (($boostScore - $averageBoostsScorePerRound) ** 2));
                }
                $standardDeviation          = sqrt(($totalSquare / array_sum($frequencies)));
                $boostStandardDeviationSum += $standardDeviation;
            }
        }

        return (float) $boostStandardDeviationSum;
    }

    // Calculate the sum of the per-reviewer standard deviations for ignored status.
    // This value can be used to determine the distribution rate of ignores in the rounds.
    // The lower the number, the better
    // http://www.wisfaq.nl/pagina.asp?nummer=1754
    private function calculateDistributionScoreIgnores(array $roundAssignments): float
    {
        // Get the average ignores
        $ignoresPerReviewerPerRound = [];
        $rounds                     = count($roundAssignments);
        $ignoreStandardDeviationSum = 0;

        foreach ($roundAssignments as $round => $projects) {
            foreach ($projects as $project) {
                foreach ($project['scores'] as $reviewer => $score) {
                    if (! isset($ignoresPerReviewerPerRound[$reviewer][$round])) {
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
                    if (! isset($frequencies[$ignores])) {
                        $frequencies[$ignores] = 0;
                    }
                    $frequencies[$ignores]++;
                }
                $totalSquare = 0;
                // Calculate sum of: [frequency] * [distance_to_average]^2
                foreach ($frequencies as $ignores => $frequency) {
                    $totalSquare += ($frequency * (($ignores - $averageIgnoresPerRound) ** 2));
                }
                $standardDeviation           = sqrt(($totalSquare / array_sum($frequencies)));
                $ignoreStandardDeviationSum += $standardDeviation;
            }
        }

        return (float) $ignoreStandardDeviationSum;
    }
}
