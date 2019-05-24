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

        ],
        'factories' => [

        ],
    ],
    'view_manager'       => [
        'template_map' => include __DIR__ . '/../template_map.php',
    ],
    'view_helpers'       => [
        'aliases'    => [

        ],
        'invokables' => [

        ],
        'factories'  => [

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

        ],
    ],
    'doctrine'           => [
        'driver' => [
            'evaluation_annotation_driver' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
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
