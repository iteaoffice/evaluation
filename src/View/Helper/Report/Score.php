<?php

declare(strict_types=1);

namespace Evaluation\View\Helper\Report;

use Evaluation\Entity\Report as EvaluationReport;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\View\Helper\AbstractHelper;

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
