<?php

/**
 * ITEA Office copyright message placeholder
 *
 * @category    Organisation
 * @package     Config
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2019 ITEA Office (https://itea3.org)
 */

namespace Evaluation;

use General\Navigation\Factory\NavigationInvokableFactory;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use General\View\Factory\LinkHelperFactory;
use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\Stdlib;

$config = [
    'controllers'        => [
        'factories' => [
            Controller\EvaluationController::class                => ConfigAbstractFactory::class,
            Controller\EvaluationManagerController::class         => ConfigAbstractFactory::class,
            Controller\FeedbackController::class                  => ConfigAbstractFactory::class,
            Controller\ReportController::class                    => ConfigAbstractFactory::class,
            Controller\ReportManagerController::class             => ConfigAbstractFactory::class,
            Controller\Report\CriterionController::class          => ConfigAbstractFactory::class,
            Controller\Report\VersionController::class            => ConfigAbstractFactory::class,
            Controller\Report\WindowController::class             => ConfigAbstractFactory::class,
            Controller\Report\Criterion\CategoryController::class => ConfigAbstractFactory::class,
            Controller\Report\Criterion\TypeController::class     => ConfigAbstractFactory::class,
            Controller\Report\Criterion\TopicController::class    => ConfigAbstractFactory::class,
            Controller\Report\Criterion\VersionController::class  => ConfigAbstractFactory::class,
            Controller\ReviewerManagerController::class           => ConfigAbstractFactory::class,
            Controller\Reviewer\ContactManagerController::class   => ConfigAbstractFactory::class,
            Controller\JsonController::class                      => ConfigAbstractFactory::class,
        ],
    ],
    'controller_plugins' => [
        'aliases'   => [
            'createEvaluation'                => Controller\Plugin\CreateEvaluation::class,
            'getEvaluationFilter'             => Controller\Plugin\GetFilter::class,
            'rosterGenerator'                 => Controller\Plugin\RosterGenerator::class,
            'evaluationReportExcelExport'     => Controller\Plugin\Report\ExcelExport::class,
            'evaluationReportExcelDownload'   => Controller\Plugin\Report\ExcelDownload::class,
            'evaluationReportPdfExport'       => Controller\Plugin\Report\PdfExport::class,
            'evaluationConsolidatedPdfExport' => Controller\Plugin\Report\ConsolidatedPdfExport::class,
            'evaluationReportExcelImport'     => Controller\Plugin\Report\ExcelImport::class,
            'evaluationReportPresentation'    => Controller\Plugin\Report\Presentation::class,
            'renderProjectEvaluation'         => Controller\Plugin\RenderProjectEvaluation::class,
        ],
        'factories' => [
            Controller\Plugin\CreateEvaluation::class             => ConfigAbstractFactory::class,
            Controller\Plugin\GetFilter::class                    => Factory\InvokableFactory::class,
            Controller\Plugin\RosterGenerator::class              => ConfigAbstractFactory::class,
            Controller\Plugin\Report\ExcelExport::class           => ConfigAbstractFactory::class,
            Controller\Plugin\Report\ExcelDownload::class         => ConfigAbstractFactory::class,
            Controller\Plugin\Report\PdfExport::class             => ConfigAbstractFactory::class,
            Controller\Plugin\Report\ConsolidatedPdfExport::class => ConfigAbstractFactory::class,
            Controller\Plugin\Report\ExcelImport::class           => ConfigAbstractFactory::class,
            Controller\Plugin\Report\Presentation::class          => ConfigAbstractFactory::class,
            Controller\Plugin\RenderProjectEvaluation::class      => ConfigAbstractFactory::class,
        ],
    ],
    'view_manager'       => [
        'template_map' => include __DIR__ . '/../template_map.php',
    ],
    'view_helpers'       => [
        'aliases' => [
            'feedbackLink'                     => View\Helper\FeedbackLink::class,
            'evaluationLink'                   => View\Helper\EvaluationLink::class,
            'evaluationReportLink'             => View\Helper\ReportLink::class,
            'evaluationReportDownloadLink'     => View\Helper\Report\DownloadLink::class,
            'evaluationReportPresentationLink' => View\Helper\Report\PresentationLink::class,
            'evaluationReportFinalLink'        => View\Helper\Report\FinalLink::class,
            'evaluationReportProgress'         => View\Helper\Report\Progress::class,
            'evaluationReportScore'            => View\Helper\Report\Score::class,
            'reportVersionLink'                => View\Helper\Report\VersionLink::class,
            'reportWindowLink'                 => View\Helper\Report\WindowLink::class,
            'reportCriterionLink'              => View\Helper\Report\CriterionLink::class,
            'reportCriterionCategoryLink'      => View\Helper\Report\Criterion\CategoryLink::class,
            'reportCriterionTypeLink'          => View\Helper\Report\Criterion\TypeLink::class,
            'reportCriterionTopicLink'         => View\Helper\Report\Criterion\TopicLink::class,
            'reportCriterionVersionLink'       => View\Helper\Report\Criterion\VersionLink::class,
            'reviewerLink'                     => View\Helper\ReviewerLink::class,
            'reviewerContactLink'              => View\Helper\Reviewer\ContactLink::class

        ],
        'invokables' => [

        ],
        'factories'  => [
            View\Helper\FeedbackLink::class                  => LinkHelperFactory::class,
            View\Helper\EvaluationLink::class                => LinkHelperFactory::class,
            View\Helper\ReportLink::class                    => LinkHelperFactory::class,
            View\Helper\Report\DownloadLink::class           => LinkHelperFactory::class,
            View\Helper\Report\PresentationLink::class       => LinkHelperFactory::class,
            View\Helper\Report\FinalLink::class              => LinkHelperFactory::class,
            View\Helper\Report\Progress::class               => ConfigAbstractFactory::class,
            View\Helper\Report\Score::class                  => ConfigAbstractFactory::class,
            View\Helper\Report\VersionLink::class            => LinkHelperFactory::class,
            View\Helper\Report\WindowLink::class             => LinkHelperFactory::class,
            View\Helper\Report\CriterionLink::class          => LinkHelperFactory::class,
            View\Helper\Report\Criterion\CategoryLink::class => LinkHelperFactory::class,
            View\Helper\Report\Criterion\TypeLink::class     => LinkHelperFactory::class,
            View\Helper\Report\Criterion\TopicLink::class    => LinkHelperFactory::class,
            View\Helper\Report\Criterion\VersionLink::class  => LinkHelperFactory::class,
            View\Helper\ReviewerLink::class                  => LinkHelperFactory::class,
            View\Helper\Reviewer\ContactLink::class          => LinkHelperFactory::class,
        ],
    ],
    'form_elements'      => [
        'aliases'   => [

        ],
        'factories' => [

        ],
    ],
    'service_manager'    => [
        'factories' => [
            // ACL
            Acl\Assertion\FeedbackAssertion::class                     => Factory\InvokableFactory::class,
            Acl\Assertion\EvaluationAssertion::class                   => Factory\InvokableFactory::class,
            Acl\Assertion\ReportAssertion::class                       => Factory\InvokableFactory::class,
            Acl\Assertion\ReviewerAssertion::class                     => Factory\InvokableFactory::class,
            //InputFilter
            InputFilter\Report\Criterion\CategoryFilter::class         => ConfigAbstractFactory::class,
            InputFilter\Report\Criterion\TopicFilter::class            => ConfigAbstractFactory::class,
            InputFilter\Report\Criterion\TypeFilter::class             => ConfigAbstractFactory::class,
            // Services
            Service\EvaluationReportService::class                     => ConfigAbstractFactory::class,
            Service\EvaluationService::class                           => ConfigAbstractFactory::class,
            Service\FormService::class                                 => ConfigAbstractFactory::class,
            Service\ReviewRosterService::class                         => ConfigAbstractFactory::class,
            Service\ReviewerService::class                             => ConfigAbstractFactory::class,
            // Navigation
            Navigation\Invokable\ReportLabel::class                    => ConfigAbstractFactory::class,
            Navigation\Invokable\Report\CriterionLabel::class          => NavigationInvokableFactory::class,
            Navigation\Invokable\Report\VersionLabel::class            => NavigationInvokableFactory::class,
            Navigation\Invokable\Report\WindowLabel::class             => NavigationInvokableFactory::class,
            Navigation\Invokable\Report\Criterion\CategoryLabel::class => NavigationInvokableFactory::class,
            Navigation\Invokable\Report\Criterion\TypeLabel::class     => NavigationInvokableFactory::class,
            Navigation\Invokable\Report\Criterion\TopicLabel::class    => NavigationInvokableFactory::class,
            Navigation\Invokable\Report\Criterion\VersionLabel::class  => NavigationInvokableFactory::class,
            Navigation\Invokable\Reviewer\ContactLabel::class          => NavigationInvokableFactory::class,
            Navigation\Invokable\EvaluateProjectLabel::class           => NavigationInvokableFactory::class,
            Navigation\Invokable\EvaluationLabel::class                => NavigationInvokableFactory::class,
            Navigation\Invokable\FeedbackLabel::class                  => NavigationInvokableFactory::class,
            // Misc
            Options\ModuleOptions::class                               => Options\Factory\ModuleOptionsFactory::class
        ],
    ],
    'doctrine'           => [
        'driver' => [
            'evaluation_annotation_driver' => [
                'class' => AnnotationDriver::class,
                'paths' => [__DIR__ . '/../src/Entity/'],
            ],
            'orm_default'                  => [
                'drivers' => [
                    'Evaluation\Entity' => 'evaluation_annotation_driver',
                ],
            ],
        ],
    ],
];
foreach (Stdlib\Glob::glob(__DIR__ . '/module.config.{,*}.php', Stdlib\Glob::GLOB_BRACE) as $file) {
    $config = Stdlib\ArrayUtils::merge($config, include $file);
}
return $config;
