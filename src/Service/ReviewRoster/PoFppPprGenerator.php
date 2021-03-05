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

use function array_diff;
use function array_key_exists;
use function array_keys;
use function array_shift;
use function array_slice;
use function ceil;
use function count;
use function end;
use function floor;
use function in_array;
use function krsort;
use function ksort;
use function rand;
use function reset;
use function shuffle;

final class PoFppPprGenerator extends AbstractGenerator
{
    protected const TEAM_RECURRENCE_FACTOR = 0.3; // In 10 rounds no more than 3x the same review team
    protected static array $assigned = [
        ReviewRosterService::REVIEWER_ASSIGNED,
        ReviewRosterService::REVIEWER_PRIMARY,
        ReviewRosterService::REVIEWER_SPARE,
        ReviewRosterService::REVIEWER_EXTRA_SPARE,
        ReviewRosterService::REVIEWER_EXTRA
    ];
    protected array $presentReviewerData;
    protected float $avgReviewActivityScore;
    protected bool $includeSpareReviewers;
    protected ?int $forceProjectsPerRound;

    public function __construct(
        array $config,
        array $projectReviewerScores,
        int $reviewersPerProject,
        float $avgReviewActivityScore,
        bool $includeSpareReviewers,
        ?int $forceProjectsPerRound = null
    ) {
        parent::__construct($config, $projectReviewerScores, $reviewersPerProject);
        $this->presentReviewerData    = $config['present'];
        $this->avgReviewActivityScore = $avgReviewActivityScore;
        $this->includeSpareReviewers  = $includeSpareReviewers;
        $this->forceProjectsPerRound  = $forceProjectsPerRound;
    }

    public function generate(): RosterData
    {
        $logger = new Logger();
        // Basic round assignment. Just divide the projects over the rounds in the order they came.
        $reviewerCount    = $this->includeSpareReviewers ? count($this->reviewerData) : count($this->presentReviewerData);
        $projectCount     = count($this->projectReviewerScores);
        $projectsPerRound = $this->preCalculateRounds(
            $projectCount,
            $this->reviewersPerProject,
            $reviewerCount,
            $this->forceProjectsPerRound
        );

        $roundAssignments = $this->initProjectRoundAssignments($projectsPerRound);
        $logger->log(
            __LINE__,
            sprintf(
                'Projects: %d, Reviewers: %d, Rounds: %d, Projects per round: %d',
                $projectCount,
                $reviewerCount,
                count($projectsPerRound),
                reset($projectsPerRound)
            )
        );

        // Optimize the distribution of score boosts and ignores among the review rounds
        $logger->log(__LINE__, 'Optimizing the distribution of score boosts and ignores among the review rounds');
        $roundAssignments = (new RoundOptimizer($projectsPerRound))->optimize($roundAssignments);

        $logger->log(__LINE__, 'Generate the roster data');
        $reviewTeams              = []; // Keep a list of created reviewer teams to keep teams mixed
        $handlesAssigned          = []; // Keep a list of assigned handles per round
        $numberOfReviewTeamRepeat = floor((self::TEAM_RECURRENCE_FACTOR * count($roundAssignments)));

        // Init roster data based on the group assignments array wiping out all boosts keeping the ignores
        $rosterData = $roundAssignments;
        foreach ($rosterData as &$rosterProjects) {
            foreach ($rosterProjects as &$rosterProjectData) {
                foreach ($rosterProjectData['scores'] as &$rosterScore) {
                    if ($rosterScore > ReviewRosterService::REVIEWER_UNASSIGNED) {
                        $rosterScore = ReviewRosterService::REVIEWER_UNASSIGNED;
                    }
                }
            }
        }

        foreach ($roundAssignments as $round => $projects) {
            $handlesAssigned[$round] = [];

            // Determine the best project match based on score for each reviewer
            $bestMatchByHandle    = [];
            $bestMatchesByProject = [];
            foreach ($projects as $projectIndex => $projectData) {
                // When there are FE reviewers, we just need to assign 1 reviewer based on FE score boost.
                // For the other FE reviewers the FE score boost can he undone and they should be assigned based on
                // regular review history score.
                $lastHistoryItem = end($projectData['data']['history']);
                if ($lastHistoryItem && array_key_exists(ReviewerService::TYPE_FE, $lastHistoryItem)) {
                    $highestScore = 0;
                    $bestMatch    = null;
                    /** @var array $feHandles */
                    $feHandles = $lastHistoryItem[ReviewerService::TYPE_FE];
                    shuffle($feHandles); // Add some randomization when FE reviewers have equal scores
                    foreach ($feHandles as $handle) {
                        // The FE handle could have been overruled by the ignored list (score = -1)
                        if (array_key_exists($handle, $projectData['scores'])) {
                            if ($projectData['scores'][$handle] > $highestScore) {
                                $highestScore = $projectData['scores'][$handle];
                                $bestMatch    = $handle;
                            }
                        }
                    }
                    // Undo the FE boost for the reviewers that weren't the best match
                    foreach ($feHandles as $handle) {
                        if (array_key_exists($handle, $projectData['scores']) && ($handle !== $bestMatch)) {
                            $projectData['scores'][$handle] -= (ReviewRosterService::$scoreBoost[ReviewerService::TYPE_FE]
                                / $this->avgReviewActivityScore);
                        }
                    }
                }

                // Determine the best matches per project and per handle
                $bestMatchesByProjectTemp            = [$projectIndex => []];
                $bestMatchesByProject[$projectIndex] = [];
                foreach ($projectData['scores'] as $handle => $score) {
                    if ($score > 0) {
                        // Score is not set yet or higher
                        if (! array_key_exists($handle, $bestMatchByHandle) || ($score > $bestMatchByHandle[$handle]['score'])) {
                            $bestMatchByHandle[$handle] = [
                                'projectIndex' => $projectIndex,
                                'score'        => $score
                            ];
                        }
                        $bestMatchesByProjectTemp[$projectIndex][(string)$score][] = [
                            'handle' => $handle,
                            'score'  => $score
                        ];
                    }
                }
                // Order from highest score to lowest
                krsort($bestMatchesByProjectTemp[$projectIndex]);
                // We just need the highest scoring handles in a descending list
                foreach ($bestMatchesByProjectTemp[$projectIndex] as $handlesPerScore) {
                    foreach ($handlesPerScore as $handleData) {
                        // Exclude spare reviewers from best matches when required
                        if ($this->includeSpareReviewers || $this->reviewerData[$handleData['handle']]['present']) {
                            $bestMatchesByProject[$projectIndex][] = $handleData;
                        }
                    }
                }
            }

            foreach ($projects as $projectIndex => $projectData) {
                $this->reviewersAssigned   = [];
                $hasExperiencedReviewer    = false;
                $hasSpareReviewer          = false;
                $hasEnoughPresentReviewers = $this->includeSpareReviewers;
                $excludeFromProject        = []; // Replace these reviewers for better mixing of review teams

                // Add the highest scoring matches per project
                if (array_key_exists($projectIndex, $bestMatchesByProject)) {
                    // Only check for mixing of teams when there are actual teams
                    if (($this->reviewersPerProject > 1)) {
                        $consideredHandles = array_slice(
                            $bestMatchesByProject[$projectIndex],
                            0,
                            $this->reviewersPerProject
                        );
                        $teamKey           = '';
                        foreach ($consideredHandles as $handleData) {
                            $teamKey .= $handleData['handle'] . '|';
                        }
                        if (! empty($teamKey)) {
                            if (! array_key_exists($teamKey, $reviewTeams)) {
                                $reviewTeams[$teamKey] = 0;
                            }
                            $reviewTeams[$teamKey]++;
                            // These reviewers have been grouped together too often, bring some variation
                            if ($reviewTeams[$teamKey] > $numberOfReviewTeamRepeat) {
                                $startIndex = 0;
                                // Check if there is a difference in reviewer scores.
                                // If there is, don't exclude the highest scoring reviewer!
                                $highestScore = $consideredHandles[0]['score'];
                                foreach ($consideredHandles as $handleData) {
                                    if ($handleData['score'] < $highestScore) {
                                        $startIndex = 1;
                                        break;
                                    }
                                }
                                $index = rand($startIndex, ($this->reviewersPerProject - 1));
                                if (array_key_exists($index, $bestMatchesByProject[$projectIndex])) {
                                    $handle               = $bestMatchesByProject[$projectIndex][$index]['handle'];
                                    $excludeFromProject[] = $handle;
                                    // When excluded from this project, also remove this project from the best matches by handle
                                    if (
                                        array_key_exists($handle, $bestMatchByHandle)
                                        && ($bestMatchByHandle[$handle]['projectIndex'] === $projectIndex)
                                    ) {
                                        unset($bestMatchByHandle[$handle]);
                                    }
                                }
                            }
                        }
                    }

                    foreach ($bestMatchesByProject[$projectIndex] as $handleData) {
                        $handle = $handleData['handle'];
                        if (
                            // Not explicitly excluded
                            ! in_array($handle, $excludeFromProject)
                            // Not yet assigned in this round when rounds are used
                            && (! in_array($handle, $handlesAssigned[$round]))
                            // Not a best match on another project, or the other project has already been assigned to other reviewers
                            && (! array_key_exists($handle, $bestMatchByHandle)
                                || ($bestMatchByHandle[$handle]['projectIndex'] <= $projectIndex))
                            // Don't assign multiple spare reviewers
                            && (! $hasSpareReviewer || $this->reviewerData[$handle]['present'])
                            // Don't add reviewers from the same organisation
                            && ! $this->sameOrganisation($handle, $this->reviewersAssigned, $this->reviewerData)
                        ) {
                            $assigned = $this->reviewerData[$handle]['present'] ?
                                ReviewRosterService::REVIEWER_ASSIGNED
                                : ReviewRosterService::REVIEWER_SPARE;
                            $rosterData[$round][$projectIndex]['scores'][$handle] = $assigned;
                            $this->reviewersAssigned[] = $handle;
                            $handlesAssigned[$round][] = $handle;
                            if ($this->reviewerData[$handle]['experienced']) {
                                $hasExperiencedReviewer = true;
                            }
                            if (! $this->reviewerData[$handle]['present']) {
                                $hasSpareReviewer = true;
                            }
                        }
                        // Already enough reviewers, no need to go further
                        if ((count($this->reviewersAssigned) === $this->reviewersPerProject)) {
                            $hasEnoughPresentReviewers = true;
                            break;
                        }
                    }
                }

                // All done here, on to the next project
                if (count($this->reviewersAssigned) === $this->reviewersPerProject) {
                    continue;
                }

                // Shuffle the reviewer list to equalize assignment chances
                $shuffledProjectData = [];
                $handles = array_keys($projectData['scores']);
                shuffle($handles);
                foreach ($handles as $handle) {
                    $shuffledProjectData[$handle] = $projectData['scores'][$handle];
                }

                // Fill with other reviewers
                foreach ($shuffledProjectData as $handle => $score) {
                    // Enough reviewers, no need to go further
                    if (count($this->reviewersAssigned) === $this->reviewersPerProject) {
                        break;
                    }
                    if (
                        // Not explicitly excluded
                        ($score > ReviewRosterService::REVIEWER_IGNORED) && ! in_array($handle, $excludeFromProject)
                        // Not yet assigned in this round when rounds are used
                        && (! in_array($handle, $handlesAssigned[$round]))
                        // Not a best match on another project, or the other project has already been assigned to other reviewers
                        && (! array_key_exists($handle, $bestMatchByHandle)
                            || ($bestMatchByHandle[$handle]['projectIndex'] <= $projectIndex))
                        // Don't assign multiple spare reviewers
                        && (! $hasSpareReviewer || $this->reviewerData[$handle]['present'])
                        // Prefer present reviewers when spare reviewers aren't counted
                        && ($hasEnoughPresentReviewers || $this->reviewerData[$handle]['present'])
                        // Don't add reviewers from the same organisation
                        && ! $this->sameOrganisation($handle, $this->reviewersAssigned, $this->reviewerData)
                    ) {
                        // When no experienced reviewer has been assigned yet, skip inexperienced reviewers
                        if (
                            (count($this->reviewersAssigned) === ($this->reviewersPerProject - 1))
                            && ! $hasExperiencedReviewer
                            && ! $this->reviewerData[$handle]['experienced']
                        ) {
                            continue;
                        }
                        // Assign the reviewer
                        $assigned = $this->reviewerData[$handle]['present'] ?
                            ReviewRosterService::REVIEWER_ASSIGNED
                            : ReviewRosterService::REVIEWER_SPARE;
                        $rosterData[$round][$projectIndex]['scores'][$handle] = $assigned;
                        $this->reviewersAssigned[] = $handle;
                        $handlesAssigned[$round][] = $handle;
                        if ($this->reviewerData[$handle]['experienced']) {
                            $hasExperiencedReviewer = true;
                        }
                        if (! $this->reviewerData[$handle]['present']) {
                            $hasSpareReviewer = true;
                        }
                    }
                }
            }
        }

        // Assign unassigned reviewers to best matching projects
        $this->assignUnassignedReviewers($rosterData, $roundAssignments, $this->reviewerData);

        return new RosterData($rosterData, $logger);
    }

    // Roughly pre-calculate review rounds. This is a heavily simplified version of the original c++ one.
    // This assumes full availability for each reviewer, not taking into account the rounds not available
    // from start or end
    private function preCalculateRounds(
        int $numberOfProjects,
        int $minReviewersAssigned,
        int $totalReviewers,
        ?int $forceProjectsPerRound = null
    ): array {
        $projectsPerRound    = [];
        $maxProjectsPerRound = floor($totalReviewers / $minReviewersAssigned);
        if (($forceProjectsPerRound !== null) && ($forceProjectsPerRound < $maxProjectsPerRound)) {
            $maxProjectsPerRound = $forceProjectsPerRound;
        }
        $rounds = ceil(($numberOfProjects / $maxProjectsPerRound));

        // As long as there are enough projects, fill the round to the maximum
        for ($round = 1; $round <= $rounds; $round++) {
            if ($numberOfProjects >= $maxProjectsPerRound) {
                $projectsPerRound[$round] = $maxProjectsPerRound;
                $numberOfProjects         -= $maxProjectsPerRound;
            } // Last round with the leftover projects
            else {
                $projectsPerRound[$round] = $numberOfProjects;
                break;
            }
        }

        return $projectsPerRound;
    }

    // Assign unassigned reviewers as extra reviewers for projects in a review round or fill up edge cases when too few
    // reviewers have been assigned
    private function assignUnassignedReviewers(array &$rosterData, array $roundAssignments, array $reviewerData): void
    {
        $allReviewers = array_keys($reviewerData);
        $lastRound = count($rosterData);

        foreach ($rosterData as $round => $projects) {
            $assignedReviewers = [];
            $projectsWithInsufficientReviewers = [];
            foreach ($projects as $projectIndex => $project) {
                $assignedReviewersPerProject = 0;
                foreach ($project['scores'] as $handle => $score) {
                    if (in_array($score, self::$assigned)) {
                        $assignedReviewers[] = $handle;
                        $assignedReviewersPerProject++;
                    }
                }
                // Edge case when not enough reviewers have been assigned by the previous steps
                if ($assignedReviewersPerProject < $this->reviewersPerProject) {
                    $projectsWithInsufficientReviewers[$projectIndex] = $assignedReviewersPerProject;
                }
            }
            $unassignedReviewers = array_diff($allReviewers, $assignedReviewers);

            // Add reviewers to projects with insufficient reviewers
            foreach ($projectsWithInsufficientReviewers as $projectIndex => $assignedReviewersPerProject) {
                for ($counter = $assignedReviewersPerProject; ($counter < $this->reviewersPerProject); $counter++) {
                    // No more reviewers left to assign, break out of this section
                    if (empty($unassignedReviewers)) {
                        break 2;
                    }
                    $handle = reset($unassignedReviewers);
                    if ($rosterData[$round][$projectIndex]['scores'][$handle] !== ReviewRosterService::REVIEWER_IGNORED) {
                        $assignedType                                         = $reviewerData[$handle]['present'] ?
                            ReviewRosterService::REVIEWER_ASSIGNED
                            : ReviewRosterService::REVIEWER_SPARE;
                        $rosterData[$round][$projectIndex]['scores'][$handle] = $assignedType;
                        array_shift($unassignedReviewers);
                    }
                }
            }

            // No extra assignments for the last round (when rounds are used)
            if (! empty($unassignedReviewers) && ($round < $lastRound)) {
                // Assign leftover unassigned reviewers to the other projects based on number of assignments and score
                while (! empty($unassignedReviewers)) {
                    shuffle($unassignedReviewers);
                    $handle = reset($unassignedReviewers);
                    $sortedProjectIndexes = $this->getLeastAssignmentsProjectIndex($rosterData[$round]);
                    $bestMatchIndex = null;
                    $bestScore = ReviewRosterService::REVIEWER_IGNORED;
                    // Iterate the projects with the least assignments and get the best matching project
                    foreach ($sortedProjectIndexes as $projectIndexes) {
                        foreach ($projectIndexes as $projectIndex) {
                            $score = $roundAssignments[$round][$projectIndex]['scores'][$handle];
                            if ($score > $bestScore) {
                                $bestMatchIndex = $projectIndex;
                                $bestScore = $score;
                            }
                        }
                        // Break out as soon as a best match has been found. Iterating further will only get less good
                        // matches, but is sometimes needed when there are a lot of blocked assignments
                        if ($bestMatchIndex !== null) {
                            break;
                        }
                    }

                    $assignedType = $this->reviewerData[$handle]['present'] ?
                        ReviewRosterService::REVIEWER_EXTRA
                        : ReviewRosterService::REVIEWER_EXTRA_SPARE;
                    $rosterData[$round][$bestMatchIndex]['scores'][$handle] = $assignedType;
                    array_shift($unassignedReviewers);
                }
            }
        }
    }

    /**
     * Get the project(s) with the least assignments in a round
     *
     * @param array $rosterDataRound
     *
     * @return array
     */
    private function getLeastAssignmentsProjectIndex(array $rosterDataRound): array
    {
        $projectAssignments = [];
        foreach ($rosterDataRound as $projectIndex => $data) {
            $projectAssignmentCount = 0;
            foreach ($data['scores'] as $score) {
                if (in_array($score, self::$assigned)) {
                    $projectAssignmentCount++;
                }
            }
            if (! array_key_exists($projectAssignmentCount, $projectAssignments)) {
                $projectAssignments[$projectAssignmentCount] = [];
            }
            // Allow for multiple projects with the same number of assignments
            $projectAssignments[$projectAssignmentCount][] = $projectIndex;
            shuffle($projectAssignments[$projectAssignmentCount]);
        }
        ksort($projectAssignments);

        return $projectAssignments;
    }
}
