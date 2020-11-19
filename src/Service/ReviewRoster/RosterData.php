<?php

declare(strict_types=1);

namespace Evaluation\Service\ReviewRoster;

final class RosterData
{
    private array $data;
    private Logger $logger;

    public function __construct(array $data, Logger $logger = null)
    {
        $this->data = $data;
        $this->logger = $logger ?? new Logger();
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }
}
