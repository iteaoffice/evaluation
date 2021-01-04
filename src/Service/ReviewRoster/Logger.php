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

use function array_keys;
use function array_merge;
use function array_walk;
use function count;
use function implode;

final class Logger
{
    private array $log = [];

    public function log(int $line, string $message): void
    {
        $this->log[] = ['l' => $line, 'm' => $message];
    }

    public function getLog(): array
    {
        return $this->log;
    }

    public function reset(): void
    {
        $this->log = [];
    }

    public function merge(Logger $logger): void
    {
        $this->log = array_merge($this->log, $logger->getLog());
    }

    // Debug round assignment data
    public static function dumpRoundAssignments(array $config, array $roundAssignments)
    {
        $reviewerData = array_merge($config['present'], $config['spare']);
        $first = true;
        $reviewerCount = 0;
        echo '<pre>' . "Round\tProj\t";
        foreach ($roundAssignments as $round => $projects) {
            foreach ($projects as $project) {
                if ($first) {
                    $reviewers = array_keys($project['scores']);
                    $reviewerCount = count($reviewers);
                    foreach ($reviewers as &$reviewer) {
                        $reviewer = $reviewer . '(' . (int) $reviewerData[$reviewer]['experienced'] . ')';
                    }
                    echo implode("\t", $reviewers) . "\r\n";
                    for ($i = 0; $i <= (($reviewerCount + 2) * 8); $i++) {
                        echo '=';
                    }
                    echo "\r\n";
                    $first = false;
                }
                echo $round . "\t" . substr($project['data']['name'], 0, 6) . "\t";
                array_walk($project['scores'], function (&$item) {
                    $item = round($item, 4);
                });
                echo implode("\t", $project['scores']);
                echo "\r\n";
            }
            for ($i = 0; $i <= (($reviewerCount + 2) * 8); $i++) {
                echo '=';
            }
            echo "\r\n";
        }
        echo '</pre>';
    }
}
