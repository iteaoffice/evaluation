<?php

/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @category    Project
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2017 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\View\Helper\Report;

use Project\Entity\Evaluation\Report2 as EvaluationReport;
use Project\Service\EvaluationReport2Service as EvaluationReportService;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\View\Helper\AbstractHelper;
use function sprintf;

/**
 * Class Progress
 * @package Evaluation\View\Helper\Report
 */
final class Progress extends AbstractHelper
{
    /**
     * @var EvaluationReportService
     */
    private $evaluationReportService;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(EvaluationReportService $evaluationReportService, TranslatorInterface $translator)
    {
        $this->evaluationReportService = $evaluationReportService;
        $this->translator              = $translator;
    }

    public function __invoke(EvaluationReport $evaluationReport = null): string
    {
        $percentage = $this->evaluationReportService->parseCompletedPercentage($evaluationReport);
        $final      = (null === $evaluationReport) ? false : $evaluationReport->getFinal();
        $template   = '<div class="progress" style="margin-bottom: 0; height:2em;">
            <div class="progress-bar bg-%s" role="progressbar" aria-valuenow="%d" aria-valuemin="0" aria-valuemax="100" style="padding-left:2px; min-width: 2em; width: %d%%;">%s</div>
        </div>';

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

        return sprintf($template, $style, $percentage, $percentage, $label);
    }
}
