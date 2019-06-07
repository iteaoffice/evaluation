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

namespace Evaluation;

use Contact\Service\SelectionContactService;
use Doctrine\ORM\EntityManager;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\ServiceManager;
use ZfcTwig\View\TwigRenderer;

return [
    ConfigAbstractFactory::class => [
        // Controllers
        Controller\Report\VersionController::class             => [
            Service\EvaluationReportService::class,
            Service\FormService::class,
            TranslatorInterface::class,
            EntityManager::class
        ],
        Controller\Report\WindowController::class             => [
            Service\EvaluationReportService::class,
            Service\FormService::class,
            TranslatorInterface::class
        ],
        Controller\Report\Criterion\CategoryController::class => [
            Service\EvaluationReportService::class,
            Service\FormService::class
        ],
        Controller\Report\Criterion\TypeController::class     => [
            Service\EvaluationReportService::class,
            Service\FormService::class,
            EntityManager::class
        ],
        Controller\Report\Criterion\TopicController::class     => [
            Service\EvaluationReportService::class,
            Service\FormService::class,
            TranslatorInterface::class
        ],
        Controller\Report\Criterion\VersionController::class   => [
            Service\EvaluationReportService::class,
            Service\FormService::class,
            TranslatorInterface::class
        ],

        // Services
        Service\EvaluationReportService::class                => [
            EntityManager::class,
            SelectionContactService::class,
        ],
        Service\FormService::class                            => [
            ServiceManager::class,
            EntityManager::class
        ]
    ]
];


