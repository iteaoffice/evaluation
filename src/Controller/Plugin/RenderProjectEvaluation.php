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

namespace Evaluation\Controller\Plugin;

use Evaluation\Entity\Feedback;
use Evaluation\Entity\Type as EvaluationType;
use Evaluation\Options\ModuleOptions;
use Evaluation\Service\EvaluationService;
use Project\Entity\Project;
use Project\Entity\Version\Type;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use ZfcTwig\View\TwigRenderer;

use function defined;
use function in_array;

/**
 * Class RenderProjectEvaluation
 *
 * @package Project\Controller\Plugin
 */
final class RenderProjectEvaluation extends AbstractPlugin
{
    private ModuleOptions $moduleOptions;
    private TwigRenderer $renderer;
    private EvaluationService $evaluationService;

    public function __construct(
        ModuleOptions $moduleOptions,
        TwigRenderer $renderer,
        EvaluationService $evaluationService
    ) {
        $this->moduleOptions = $moduleOptions;
        $this->renderer = $renderer;
        $this->evaluationService = $evaluationService;
    }

    public function render(
        Project $project,
        EvaluationType $evaluationType,
        Type $versionType,
        $evaluationResult,
        $countries,
        Feedback $feedback = null
    ): ReportPdf {
        $pdf = new ReportPdf();

        //Doe some nasty hardcoded stuff for AENEAS
        $pdf->setTemplate($this->moduleOptions->getProjectTemplate());
        $pdf->Line(15, 60, 191, 60, ['color' => [0, 166, 81]]);

        $y = 35;

        $pdf->AddPage();
        $pdf->header();
        $pdf->SetFontSize(10);
        $pdf->setPageMark();
        $pdf->SetMargins(24, $y);

        $projectEvaluationOverview = $this->renderer->render(
            'evaluation/partial/pdf/evaluation-project-overview',
            [
                'countries'        => $countries,
                'project'          => $project,
                'evaluationType'   => $evaluationType,
                'versionType'      => $versionType,
                'evaluationTypes'  => $this->evaluationService->findAll(EvaluationType::class),
                'evaluationResult' => $evaluationResult,
                'fundingStatuses'  => $this->evaluationService->getFundingStatusList(
                    $this->evaluationService
                        ->parseMainEvaluationType($evaluationType)
                ),
                'isEvaluation'     => $this->evaluationService->isEvaluation($evaluationType),
                'feedback'         => $feedback,
            ]
        );

        $pdf->writeHTMLCell(0, 0, 14, $y, $projectEvaluationOverview);

        return $pdf;
    }
}
