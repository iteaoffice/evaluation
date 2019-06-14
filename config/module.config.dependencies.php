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

use Affiliation\Service\AffiliationService;
use Contact\Service\SelectionContactService;
use Doctrine\ORM\EntityManager;
use General\Service\CountryService;
use Project\Service\ProjectService;
use Project\Service\VersionService;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\ServiceManager;
use ZfcTwig\View\TwigRenderer;

return [
    ConfigAbstractFactory::class => [
        // Controllers
        Controller\ReportController::class                    => [
            Service\EvaluationReportService::class,
            ProjectService::class,
            EntityManager::class,
            TranslatorInterface::class,
        ],
        Controller\ReportManagerController::class             => [
            Service\EvaluationReportService::class,
            EntityManager::class,
            TranslatorInterface::class
        ],
        Controller\Report\CriterionController::class          => [
            Service\EvaluationReportService::class,
            Service\FormService::class,
            EntityManager::class,
            TranslatorInterface::class
        ],
        Controller\Report\VersionController::class            => [
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
        Controller\Report\Criterion\TopicController::class    => [
            Service\EvaluationReportService::class,
            Service\FormService::class,
            TranslatorInterface::class
        ],
        Controller\Report\Criterion\VersionController::class  => [
            Service\EvaluationReportService::class,
            Service\FormService::class,
            TranslatorInterface::class
        ],

        // Controller plugins
        Controller\Plugin\Report\ExcelExport::class           => [
            Service\EvaluationReportService::class,
            ProjectService::class,
            VersionService::class,
            Options\ModuleOptions::class,
            TranslatorInterface::class
        ],
        Controller\Plugin\Report\ExcelImport::class           => [
            Service\EvaluationReportService::class
        ],
        Controller\Plugin\Report\PdfExport::class             => [
            Service\EvaluationReportService::class,
            ProjectService::class,
            VersionService::class,
            Options\ModuleOptions::class,
            TranslatorInterface::class
        ],
        Controller\Plugin\Report\ConsolidatedPdfExport::class => [
            Service\EvaluationReportService::class,
            ProjectService::class,
            VersionService::class,
            Service\EvaluationService::class,
            AffiliationService::class,
            CountryService::class,
            Options\ModuleOptions::class,
            'ControllerPluginManager',
            TwigRenderer::class,
            TranslatorInterface::class
        ],
        Controller\Plugin\Report\Presentation::class          => [
            Service\EvaluationReportService::class,
            ProjectService::class,
            VersionService::class,
            Options\ModuleOptions::class,
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
        ],

        // View helpers
        View\Helper\Report\Progress::class                    => [
            Service\EvaluationReportService::class,
            TranslatorInterface::class
        ],
    ]
];


