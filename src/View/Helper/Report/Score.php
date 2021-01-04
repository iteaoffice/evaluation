<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\View\Helper\Report;

use Evaluation\Entity\Report as EvaluationReport;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\View\Helper\AbstractHelper;

/**
 * Class Score
 *
 * @package Evaluation\View\Helper\Report
 */
final class Score extends AbstractHelper
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function __invoke(EvaluationReport $evaluationReport): string
    {
        $scores = EvaluationReport::getVersionScores() + EvaluationReport::getReportScores();
        if (isset($scores[$evaluationReport->getScore()])) {
            return $this->translator->translate($scores[$evaluationReport->getScore()]);
        }

        return '';
    }
}
