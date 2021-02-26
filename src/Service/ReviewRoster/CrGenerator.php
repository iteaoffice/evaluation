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

use function array_key_exists;
use function array_keys;
use function asort;
use function krsort;
use function reset;
use function sprintf;

class CrGenerator extends AbstractGenerator
{
    protected array $reviewerLoad = [];

    public function generate(): RosterData
    {
        $this->logger->reset();
        $this->initReviewerLoad();
        $assignments = $this->initProjectAssignments();
        $bestMatchesByProject = $this->getBestMatchesPerProject();

        // Assign reviewers
        foreach ($assignments as $projectIndex => &$assignment) {
            $this->reviewersAssigned = [];
            $hasPrimaryReviewer = false;

            // If future evaluation is set, get reviewers from there
            $lastHistoryItem = end($this->projectReviewerScores[$projectIndex]['data']['history']);
            if ($lastHistoryItem && array_key_exists(ReviewerService::TYPE_FE, $lastHistoryItem)) {
                $this->assignFutureEvaluationReviewers($assignment, $projectIndex);
            }
            // Otherwise, add the highest scoring match per project
            elseif (count($bestMatchesByProject[$projectIndex]) > 0) {
                $this->assignBestProjectMatch($assignment, $bestMatchesByProject[$projectIndex]);
            }

            // Make one of the above reviewers the primary reviewer.
            // Inexperienced reviewers should not become primary reviewer.
            if (count($this->reviewersAssigned) > 0) {
                $this->assignRandomPrimaryReviewer($assignment);
                $hasPrimaryReviewer = true;
            }

            // Add randomly picked other reviewers based on reviewer workload
            $this->assignRandomReviewers($assignment);

            // No primary reviewer has been assigned based on experience or FE label. Just pick a random one.
            if (! $hasPrimaryReviewer) {
                $this->assignRandomPrimaryReviewer($assignment, false);
            }
        }

        return new RosterData($this->sortByCall($assignments), $this->logger);
    }

    protected function initReviewerLoad(): void
    {
        $this->reviewerLoad = [];
        foreach (array_keys($this->reviewerData) as $handle) {
            $this->reviewerLoad[$handle] = 0;
        }
    }

    protected function assignFutureEvaluationReviewers(array &$assignment, int $projectIndex): void
    {
        $lastHistoryItem = end($this->projectReviewerScores[$projectIndex]['data']['history']);
        foreach ($lastHistoryItem[ReviewerService::TYPE_FE] as $handle) {
            if (count($this->reviewersAssigned) === $this->reviewersPerProject) {
                break;
            }
            if (
                array_key_exists($handle, $this->reviewerData)
                // Prevent rare cases where a preferred reviewer is also an ignored one (after org merge)
                && ($this->projectReviewerScores[$projectIndex]['scores'][$handle] !== ReviewRosterService::REVIEWER_IGNORED)
                // Prevent reviewers from the same company being added
                && ! $this->sameOrganisation($handle, $this->reviewersAssigned, $this->reviewerData)
            ) {
                $this->reviewersAssigned[] = $handle;
                $this->reviewerLoad[$handle]++;
                $assignment['scores'][$handle] = ReviewRosterService::REVIEWER_ASSIGNED;
                $this->logger->log(
                    __LINE__,
                    sprintf(
                        '%s: %s assigned based on FE',
                        $assignment['data']['number'] . ' ' . $assignment['data']['name'],
                        $handle
                    )
                );
            }
        }
    }

    protected function assignBestProjectMatch(array &$assignment, array $bestProjectMatches): void
    {
        $handle = reset($bestProjectMatches);
        // Multiple best matches? Take the one with the least assignments.
        if (count($bestProjectMatches) > 1) {
            asort($this->reviewerLoad);
            foreach (array_keys($this->reviewerLoad) as $reviewerHandle) {
                if (in_array($reviewerHandle, $bestProjectMatches)) {
                    $handle = $reviewerHandle;
                    break;
                }
            }
        }
        $this->reviewersAssigned[] = $handle;
        $this->reviewerLoad[$handle]++;
        $assignment['scores'][$handle] = ReviewRosterService::REVIEWER_ASSIGNED;
        $this->logger->log(
            __LINE__,
            sprintf(
                '%s: %s assigned based on best scoring project matches',
                $assignment['data']['number'] . ' ' . $assignment['data']['name'],
                $handle
            )
        );
    }

    protected function assignRandomReviewers(array &$assignment): void
    {
        $iteration = 1; // Fail safe to prevent infinite loop
        asort($this->reviewerLoad);
        $reviewers = count($this->reviewerData);
        while ((count($this->reviewersAssigned) < $this->reviewersPerProject) && ($iteration <= $reviewers)) {
            foreach (array_keys($this->reviewerLoad) as $handle) {
                // Add reviewers with low load and not from the same organisation as reviewers already assigned
                if (
                    ($assignment['scores'][$handle] === 0)
                    && ! $this->sameOrganisation($handle, $this->reviewersAssigned, $this->reviewerData)
                ) {
                    $this->reviewersAssigned[] = $handle;
                    $this->reviewerLoad[$handle]++;
                    $assignment['scores'][$handle] = ReviewRosterService::REVIEWER_ASSIGNED;
                    asort($this->reviewerLoad);
                    $this->logger->log(
                        __LINE__,
                        sprintf(
                            '%s: %s assigned randomly based on reviewer workload',
                            $assignment['data']['number'] . ' ' . $assignment['data']['name'],
                            $handle
                        )
                    );
                    break 1;
                }
            }
            $iteration++;
        }
    }

    private function assignRandomPrimaryReviewer(array &$assignment, bool $fromPreference = true): void
    {
        $handle = $this->reviewersAssigned[array_rand($this->reviewersAssigned)];
        $assignment['scores'][$handle] = ReviewRosterService::REVIEWER_PRIMARY;
        $template = $fromPreference ? '%s: %s made primary reviewer randomly selected from FE and best project matches'
            : '%s: No primary reviewer based on experience or FE label, randomly assigned %s as primary reviewer';
        $this->logger->log(
            __LINE__,
            sprintf($template, $assignment['data']['number'] . ' ' . $assignment['data']['name'], $handle)
        );
    }

    // Sort by call DESC
    private function sortByCall(array $assignments): array
    {
        $rosterData = [];
        foreach ($assignments as $assignment) {
            if (! array_key_exists($assignment['data']['call'], $rosterData)) {
                $rosterData[$assignment['data']['call']] = [];
            }
            $rosterData[$assignment['data']['call']][] = $assignment;
        }
        krsort($rosterData);

        return $rosterData;
    }
}
