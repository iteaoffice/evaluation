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
use Contact\Service\ContactService;
use Contact\Service\SelectionContactService;
use Doctrine\ORM\EntityManager;
use General\Service\CountryService;
use General\Service\GeneralService;
use Program\Service\CallService;
use Project\Controller\Plugin;
use Project\Service\ProjectService;
use Project\Service\VersionService;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use ZfcTwig\View\TwigRenderer;

return [
    /*ConfigAbstractFactory::class => [
        // Controllers
        Controller\EvaluationController::class               => [
            ProjectService::class,
            VersionService::class,
            Service\EvaluationService::class,
            CallService::class,
            ContactService::class,
            GeneralService::class,
            CountryService::class,
            Service\FormService::class,
            EntityManager::class,
            TranslatorInterface::class
        ],
        Controller\EvaluationManagerController::class        => [
            ProjectService::class,
            Service\EvaluationService::class,
            CallService::class,
            EntityManager::class
        ],
        Controller\ReportController::class                   => [
            Service\EvaluationReportService::class,
            ProjectService::class,
            TranslatorInterface::class,
            EntityManager::class
        ],
        Controller\ReportCriterionCategoryController::class  => [
            Service\EvaluationReportService::class,
            Service\FormService::class
        ],
        Controller\ReportCriterionController::class          => [
            EntityManager::class,
            Service\EvaluationReportService::class,
            Service\FormService::class
        ],
        Controller\ReportCriterionVersionController::class   => [
            Service\EvaluationReportService::class,
            Service\FormService::class
        ],
        Controller\ReportCriterionTopicController::class     => [
            Service\EvaluationReportService::class,
            Service\FormService::class
        ],
        Controller\ReportCriterionTypeController::class      => [
            Service\EvaluationReportService::class,
            Service\FormService::class
        ],
        Controller\ReportVersionController::class            => [
            EntityManager::class,
            Service\EvaluationReportService::class,
            Service\FormService::class
        ],
        Controller\ReportWindowController::class             => [
            Service\EvaluationReportService::class,
            Service\FormService::class
        ],
        Controller\ReportManagerController::class            => [
            EntityManager::class,
            Service\EvaluationReportService::class,
            TranslatorInterface::class
        ],
        // Controller plugins
        Plugin\CreateEvaluation::class                       => [
            ProjectService::class,
            VersionService::class,
            Service\EvaluationService::class,
            AffiliationService::class,
            CountryService::class
        ],
        Plugin\RenderProjectEvaluation::class                => [
            Options\ModuleOptions::class,
            TwigRenderer::class,
            Service\EvaluationService::class
        ],
        Plugin\Evaluation\ReportExcelExport::class           => [
            Service\EvaluationReportService::class,
            ProjectService::class,
            VersionService::class,
            Options\ModuleOptions::class,
            TranslatorInterface::class
        ],
        Plugin\Evaluation\ReportExcelImport::class           => [
            Service\EvaluationReportService::class
        ],
        Plugin\Evaluation\ReportPdfExport::class             => [
            Service\EvaluationReportService::class,
            ProjectService::class,
            VersionService::class,
            Options\ModuleOptions::class,
            TranslatorInterface::class
        ],
        Plugin\Evaluation\ReportConsolidatedPdfExport::class => [
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
        Plugin\Evaluation\ReportPresentation::class          => [
            Service\EvaluationReportService::class,
            ProjectService::class,
            VersionService::class,
            Options\ModuleOptions::class,
            TranslatorInterface::class
        ],
        Plugin\Evaluation\RosterGenerator::class             => [
            Service\ReviewRosterService::class,
            TranslatorInterface::class,
            Options\ModuleOptions::class
        ],
        // Services
        Service\EvaluationReportService::class               => [
            EntityManager::class,
            SelectionContactService::class,
        ],
        // View helpers
        View\Helper\ReportProgress::class                    => [
            Service\EvaluationReportService::class,
            TranslatorInterface::class
        ],
    ]*/
];


