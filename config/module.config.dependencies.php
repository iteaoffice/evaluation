<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation;

use Affiliation\Service\AffiliationService;
use Contact\Service\ContactService;
use Contact\Service\SelectionContactService;
use Doctrine\ORM\EntityManager;
use Evaluation\Options\ModuleOptions;
use Evaluation\Service\EvaluationService;
use Evaluation\Service\FormService;
use General\Navigation\Service\NavigationService;
use General\Service\CountryService;
use General\Service\GeneralService;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\ServiceManager;
use Program\Service\CallService;
use Project\Search\Service\ProjectSearchService;
use Project\Service\ProjectService;
use Project\Service\ReportService;
use Project\Service\VersionService;
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
        Controller\FeedbackController::class                  => [
            Service\EvaluationService::class,
            VersionService::class,
            Service\FormService::class,
            TranslatorInterface::class
        ],
        Controller\EvaluationController::class                => [
            ProjectService::class,
            VersionService::class,
            EvaluationService::class,
            CallService::class,
            ContactService::class,
            GeneralService::class,
            CountryService::class,
            FormService::class,
            EntityManager::class,
            TranslatorInterface::class,
        ],
        Controller\EvaluationManagerController::class         => [
            ProjectService::class,
            EvaluationService::class,
            CallService::class,
            EntityManager::class
        ],
        Controller\ReportManagerController::class             => [
            Service\EvaluationReportService::class,
            VersionService::class,
            ReportService::class,
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
        Controller\ReviewerManagerController::class           => [
            Service\ReviewerService::class,
            ProjectService::class,
            Service\FormService::class,
            EntityManager::class,
            TranslatorInterface::class
        ],
        Controller\Reviewer\ContactManagerController::class   => [
            Service\ReviewerService::class,
            Service\FormService::class
        ],
        Controller\JsonController::class                      => [
            CallService::class,
            EvaluationService::class,
            ProjectService::class,
            CountryService::class,
            'ViewHelperManager'
        ],
        // Controller plugins
        Controller\Plugin\CreateEvaluation::class             => [
            ProjectService::class,
            VersionService::class,
            EvaluationService::class,
            AffiliationService::class,
            CountryService::class
        ],
        Controller\Plugin\RosterGenerator::class              => [
            Service\ReviewRosterService::class,
            TranslatorInterface::class,
            Options\ModuleOptions::class
        ],
        Controller\Plugin\Report\ExcelExport::class           => [
            Service\EvaluationReportService::class,
            ProjectService::class,
            VersionService::class,
            Options\ModuleOptions::class,
            TranslatorInterface::class
        ],
        Controller\Plugin\Report\ExcelDownload::class         => [
            Service\EvaluationReportService::class,
            'ControllerPluginManager',
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
            Options\ModuleOptions::class,
            TranslatorInterface::class
        ],
        Controller\Plugin\RenderProjectEvaluation::class      => [
            ModuleOptions::class,
            TwigRenderer::class,
            Service\EvaluationService::class
        ],

        // Input filter
        InputFilter\Report\Criterion\CategoryFilter::class    => [
            EntityManager::class
        ],
        InputFilter\Report\Criterion\TopicFilter::class       => [
            EntityManager::class
        ],
        InputFilter\Report\Criterion\TypeFilter::class        => [
            EntityManager::class
        ],

        // Services
        Service\EvaluationService::class                      => [
            EntityManager::class
        ],
        Service\EvaluationReportService::class                => [
            EntityManager::class,
            SelectionContactService::class,
        ],
        Service\FormService::class                            => [
            ServiceManager::class,
            EntityManager::class
        ],
        Service\ReviewerService::class                        => [
            EntityManager::class,
        ],
        Service\ReviewRosterService::class                    => [
            CallService::class,
            ProjectService::class,
            ProjectSearchService::class,
            Service\ReviewerService::class,
            EntityManager::class,
        ],

        // View helpers
        View\Helper\Report\Progress::class                    => [
            Service\EvaluationReportService::class,
            TranslatorInterface::class
        ],
        View\Helper\Report\Score::class                       => [
            TranslatorInterface::class
        ],
        Navigation\Invokable\ReportLabel::class               => [
            NavigationService::class,
            TranslatorInterface::class,
            Service\EvaluationReportService::class,
        ]
    ]
];
