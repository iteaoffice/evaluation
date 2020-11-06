<?php

declare(strict_types=1);

namespace Evaluation\Service\ReviewRoster;

abstract class AbstractGenerator implements Generator
{
    protected array $reviewerData;
    protected array $projectReviewerScores;
    protected int $reviewersPerProject;

    public function __construct(array $config, array $projectReviewerScores, int $reviewersPerProject)
    {
        $this->projectReviewerScores = $projectReviewerScores;
        $this->reviewerData = array_merge($config['present'], $config['spare']);
        $this->reviewersPerProject = $reviewersPerProject;
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
}
