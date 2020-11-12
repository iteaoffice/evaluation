<?php

declare(strict_types=1);

namespace Evaluation\Service\ReviewRoster;

use Evaluation\Service\ReviewRosterService;

use function array_values;
use function current;
use function ksort;
use function next;
use function reset;
use function shuffle;

abstract class AbstractGenerator implements Generator
{
    protected Logger $logger;
    protected array $reviewerData;
    protected array $projectReviewerScores;
    protected array $reviewersAssigned = [];
    protected int $reviewersPerProject;

    public function __construct(array $config, array $projectReviewerScores, int $reviewersPerProject)
    {
        $this->projectReviewerScores = $projectReviewerScores;
        $this->reviewerData = array_merge($config['present'], $config['spare']);
        $this->reviewersPerProject = $reviewersPerProject;
        $this->logger = new Logger();
    }

    public abstract function generate(): RosterData;

    // Check whether reviewers are from the same (parent) organisation
    protected function sameOrganisation(string $handle, array $otherHandles, array $reviewerData): bool
    {
        foreach ($otherHandles as $otherHandle) {
            if (
                ($reviewerData[$handle]['organisation'] === $reviewerData[$otherHandle]['organisation'])
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

    protected function getBestMatchesPerProject(): array
    {
        $bestMatchesByProject = [];
        foreach ($this->projectReviewerScores as $projectIndex => $project) {
            $bestMatchesByProject[$projectIndex] = [];
            $highestScore = 0;
            foreach ($project['scores'] as $handle => $score) {
                if ($score > $highestScore) {
                    $bestMatchesByProject[$projectIndex] = [$handle];
                    $highestScore = $score;
                } elseif ($score === $highestScore) { // Multiple highest scores get added and shuffled
                    $bestMatchesByProject[$projectIndex][] = $handle;
                }
            }
            shuffle($bestMatchesByProject[$projectIndex]);
        }

        return $bestMatchesByProject;
    }

    // Init assignment data based on the project reviewer scores array wiping out all boosts keeping the ignores
    protected function initProjectAssignments(): array
    {
        $projectAssignments = $this->projectReviewerScores;
        foreach ($projectAssignments as &$assignmentsProjectData) {
            foreach ($assignmentsProjectData['scores'] as &$assignmentScore) {
                if ($assignmentScore > 0) {
                    $assignmentScore = ReviewRosterService::REVIEWER_UNASSIGNED;
                }
            }
        }
        // Sort by project number
        $projectAssignmentsSorted = [];
        foreach ($projectAssignments as $projectAssignment) {
            $projectAssignmentsSorted[$projectAssignment['data']['number']] = $projectAssignment;
        }
        ksort($projectAssignmentsSorted);

        return array_values($projectAssignmentsSorted);
    }

    // Basic round assignment. Just divide the projects over the rounds in the order they came.
    protected function initProjectRoundAssignments(array $projectsPerRound): array
    {
        $roundAssignments = [];
        reset($this->projectReviewerScores);
        foreach ($projectsPerRound as $round => $numberOfProjects) {
            if (!isset($roundAssignments[$round])) {
                $roundAssignments[$round] = [];
            }
            for ($i = 0; $i < $numberOfProjects; $i++) {
                $roundAssignments[$round][] = current($this->projectReviewerScores);
                next($this->projectReviewerScores);
            }
        }

        return $roundAssignments;
    }
}
