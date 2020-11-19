<?php

declare(strict_types=1);

namespace Evaluation\Service\ReviewRoster;

interface Generator
{
    public function generate(): RosterData;
}
