<?php
/**
*
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2019 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\View\Helper\Report;

use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Service\EvaluationReportService;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\View\Helper\AbstractHelper;
use function round;
use function sprintf;

/**
 * Class Progress
 * @package Evaluation\View\Helper\Report
 */
final class Progress extends AbstractHelper
{
    private EvaluationReportService $evaluationReportService;
    private TranslatorInterface     $translator;
    private string                  $template = '<div class="progress" style="margin-bottom: 0; height:2em;">
            <div class="progress-bar bg-%s" role="progressbar" aria-valuenow="%d" aria-valuemin="0" aria-valuemax="100" style="padding-left:2px; min-width: 2em; width: %d%%;">%s</div>
        </div>';

    public function __construct(EvaluationReportService $evaluationReportService, TranslatorInterface $translator)
    {
        $this->evaluationReportService = $evaluationReportService;
        $this->translator              = $translator;
    }

    public function __invoke(EvaluationReport $evaluationReport = null): string
    {
        $percentage = round($this->evaluationReportService->parseCompletedPercentage($evaluationReport));
        $final      = (null === $evaluationReport) ? false : $evaluationReport->getFinal();

        $style = 'danger';
        if ((int)$percentage === 100) {
            $style = 'success';
        } elseif ($percentage > 49) {
            $style = 'warning';
        }

        $label = $percentage . '% ' . $this->translator->translate('txt-completed');
        if ($final) {
            $label .= ' + ' . $this->translator->translate('txt-final');
        }

        return sprintf($this->template, $style, $percentage, $percentage, $label);
    }

    public function getTemplate(): string
    {
        return $this->template;
    }
}
