<?php
/**
 * ITEA Office copyright message placeholder
 *
 * @category    Organisation
 * @package     Config
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2017 ITEA Office (https://itea3.org)
 */

namespace Evaluation;

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Evaluation\View\Factory\ViewHelperFactory;
use Evaluation\View\Helper\Report\DownloadLink;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\Stdlib;

$config = [
    'controllers'        => [
        'factories' => [
            Controller\EvaluationController::class                => ConfigAbstractFactory::class,
            Controller\EvaluationManagerController::class         => ConfigAbstractFactory::class,
            Controller\FeedbackController::class                  => ConfigAbstractFactory::class,
            Controller\ReportController::class                    => ConfigAbstractFactory::class,
            Controller\ReportManagerController::class             => ConfigAbstractFactory::class,
            Controller\ReviewManagerController::class             => ConfigAbstractFactory::class,
            Controller\Report\CriterionController::class          => ConfigAbstractFactory::class,
            Controller\Report\VersionController::class            => ConfigAbstractFactory::class,
            Controller\Report\WindowController::class             => ConfigAbstractFactory::class,
            Controller\Report\Criterion\CategoryController::class => ConfigAbstractFactory::class,
            Controller\Report\Criterion\TypeController::class     => ConfigAbstractFactory::class,
            Controller\Report\Criterion\TopicController::class    => ConfigAbstractFactory::class,
            Controller\Report\Criterion\VersionController::class  => ConfigAbstractFactory::class,
        ],
    ],
    'controller_plugins' => [
        'aliases'   => [
            'getEvaluationFilter'          => Controller\Plugin\GetFilter::class,
            'evaluationReport2ExcelExport' => Controller\Plugin\Report\ExcelExport::class,
        ],
        'factories' => [
            Controller\Plugin\GetFilter::class                    => Factory\InvokableFactory::class,
            Controller\Plugin\RosterGenerator::class              => ConfigAbstractFactory::class,
            Controller\Plugin\Report\ExcelExport::class           => ConfigAbstractFactory::class,
            Controller\Plugin\Report\ExcelDownload::class         => ConfigAbstractFactory::class,
            Controller\Plugin\Report\PdfExport::class             => ConfigAbstractFactory::class,
            Controller\Plugin\Report\ConsolidatedPdfExport::class => ConfigAbstractFactory::class,
            Controller\Plugin\Report\ExcelImport::class           => ConfigAbstractFactory::class,
            Controller\Plugin\Report\Presentation::class          => ConfigAbstractFactory::class,
        ],
    ],
    'view_manager'       => [
        'template_map' => include __DIR__ . '/../template_map.php',
    ],
    'view_helpers'       => [
        'aliases'    => [
            'evaluationReport2Link'            => View\Helper\ReportLink::class,
            'evaluationReport2DownloadLink'    => View\Helper\Report\DownloadLink::class,
            'evaluationReport2FinalLink'       => View\Helper\Report\FinalLink::class,
            'evaluationReport2Progress'        => View\Helper\Report\Progress::class,
            'evaluationReport2Score'           => View\Helper\Report\Score::class,
            'report2VersionLink'               => View\Helper\Report\VersionLink::class,
            'report2WindowLink'                => View\Helper\Report\WindowLink::class,
            'report2CriterionLink'             => View\Helper\Report\CriterionLink::class,
            'report2CriterionCategoryLink'     => View\Helper\Report\Criterion\CategoryLink::class,
            'report2CriterionTypeLink'         => View\Helper\Report\Criterion\TypeLink::class,
            'report2CriterionTopicLink'        => View\Helper\Report\Criterion\TopicLink::class,
            'report2CriterionVersionLink'      => View\Helper\Report\Criterion\VersionLink::class,

        ],
        'invokables' => [

        ],
        'factories'  => [
            View\Helper\ReportLink::class                    => ViewHelperFactory::class,
            View\Helper\Report\DownloadLink::class           => ViewHelperFactory::class,
            View\Helper\Report\FinalLink::class              => ViewHelperFactory::class,
            View\Helper\Report\Progress::class               => ConfigAbstractFactory::class,
            View\Helper\Report\Score::class                  => ConfigAbstractFactory::class,
            View\Helper\Report\VersionLink::class            => ViewHelperFactory::class,
            View\Helper\Report\WindowLink::class             => ViewHelperFactory::class,
            View\Helper\Report\CriterionLink::class          => ViewHelperFactory::class,
            View\Helper\Report\Criterion\CategoryLink::class => ViewHelperFactory::class,
            View\Helper\Report\Criterion\TypeLink::class     => ViewHelperFactory::class,
            View\Helper\Report\Criterion\TopicLink::class    => ViewHelperFactory::class,
            View\Helper\Report\Criterion\VersionLink::class  => ViewHelperFactory::class,
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
            // Services
            Service\EvaluationReportService::class                     => ConfigAbstractFactory::class,
            Service\EvaluationService::class                           => ConfigAbstractFactory::class,
            Service\FormService::class                                 => ConfigAbstractFactory::class,
            Service\ReviewRosterService::class                         => ConfigAbstractFactory::class,
            Service\ReviewService::class                               => ConfigAbstractFactory::class,
            // Navigation
            Navigation\Invokable\ReportLabel::class                    => Factory\InvokableFactory::class,
            Navigation\Invokable\Report\CriterionLabel::class          => Factory\InvokableFactory::class,
            Navigation\Invokable\Report\VersionLabel::class            => Factory\InvokableFactory::class,
            Navigation\Invokable\Report\WindowLabel::class             => Factory\InvokableFactory::class,
            Navigation\Invokable\Report\Criterion\CategoryLabel::class => Factory\InvokableFactory::class,
            Navigation\Invokable\Report\Criterion\TypeLabel::class     => Factory\InvokableFactory::class,
            Navigation\Invokable\Report\Criterion\TopicLabel::class    => Factory\InvokableFactory::class,
            Navigation\Invokable\Report\Criterion\VersionLabel::class  => Factory\InvokableFactory::class,
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
            'orm_default'                    => [
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
