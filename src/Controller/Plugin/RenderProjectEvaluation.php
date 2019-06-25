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

namespace Evaluation\Controller\Plugin;

use Evaluation\Entity\Feedback;
use Evaluation\Entity\Type as EvaluationType;
use Evaluation\Service\EvaluationService;
use Project\Entity\Project;
use Project\Entity\Version\Type;
use Project\Options\ModuleOptions;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
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
    /**
     * @var ModuleOptions
     */
    private $moduleOptions;
    /**
     * @var TwigRenderer
     */
    private $renderer;
    /**
     * @var EvaluationService
     */
    private $evaluationService;

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
        $pdf->setTemplate($this->moduleOptions->getEvaluationProjectTemplate());

        $y = 35;

        //@todo Change this so the template is taken from the program
        if (defined('ITEAOFFICE_HOST') && ITEAOFFICE_HOST === 'aeneas') {
            $originalTemplate = $this->moduleOptions->getEvaluationProjectTemplate();

            $template = $originalTemplate;
            if (in_array('Penta', $project->parsePrograms(), true)) {
                $template = str_replace('blank-template-firstpage', 'penta-template', $originalTemplate);
            }

            if (in_array('EURIPIDES', $project->parsePrograms(), true)) {
                $template = str_replace('blank-template-firstpage', 'euripides-template', $originalTemplate);
            }

            if (in_array('Penta', $project->parsePrograms(), true)
                && in_array(
                    'EURIPIDES',
                    $project->parsePrograms(),
                    true
                )
            ) {
                $template = str_replace('blank-template-firstpage', 'penta-euripides-template', $originalTemplate);
            }

            $y = 60;

            $pdf->setTemplate($template);
        } else {
            $pdf->Line(15, 60, 191, 60, ['color' => [0, 166, 81]]);
        }

        $pdf->AddPage();
        $pdf->header();
        $pdf->SetFontSize(10);
        $pdf->setPageMark();


        $projectEvaluationOverview = $this->renderer->render(
            'project/pdf/evaluation-project-overview',
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
