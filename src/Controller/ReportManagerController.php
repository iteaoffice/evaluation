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

namespace Evaluation\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as PaginatorAdapter;
use Evaluation\Controller\Plugin\GetFilter;
use Evaluation\Controller\Plugin\Report\ConsolidatedPdfExport;
use Evaluation\Controller\Plugin\Report\ExcelExport;
use Evaluation\Controller\Plugin\Report\ExcelImport;
use Evaluation\Controller\Plugin\Report\PdfExport;
use Evaluation\Controller\Plugin\Report\Presentation;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Entity\Report\ProjectReport as ProjectReportReport;
use Evaluation\Entity\Report\ProjectVersion as ProjectVersionReport;
use Evaluation\Entity\Report\Result as ReportResult;
use Evaluation\Entity\Report\Type as ReportType;
use Evaluation\Form\ReportFilter;
use Evaluation\Form\ReportUpload;
use Evaluation\Service\EvaluationReportService;
use Project\Entity\Report\Report as ProjectReport;
use Project\Entity\Version\Version as ProjectVersion;
use Project\Service\ReportService;
use Project\Service\VersionService;
use Zend\Http\Request;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;
use ZfcUser\Controller\Plugin\ZfcUserAuthentication;
use function array_keys;
use function array_merge;
use function ceil;
use function reset;
use function unlink;
use const PHP_INT_MAX;

/**
 * @method ZfcUserAuthentication zfcUserAuthentication()
 * @method FlashMessenger flashMessenger()
 * @method GetFilter getEvaluationFilter()
 * @method ExcelExport evaluationReportExcelExport(EvaluationReport $evaluationReport, bool $isFinal = false, bool $forDistribution = false)
 * @method PdfExport evaluationReportPdfExport(EvaluationReport $evaluationReport, bool $forDistribution = false)
 * @method ConsolidatedPdfExport evaluationConsolidatedPdfExport(EvaluationReport $evaluationReport)
 * @method ExcelImport evaluationReportExcelImport(string $file)
 * @method Presentation evaluationReportPresentation(array $evaluationReports)
 */
final class ReportManagerController extends AbstractActionController
{
    /**
     * @var EvaluationReportService
     */
    private $evaluationReportService;
    /**
     * @var VersionService
     */
    private $versionService;
    /**
     * @var ReportService
     */
    private $reportService;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        EvaluationReportService $evaluationReportService,
        VersionService          $versionService,
        ReportService           $reportService,
        EntityManager           $entityManager,
        TranslatorInterface     $translator
    ) {
        $this->evaluationReportService = $evaluationReportService;
        $this->versionService          = $versionService;
        $this->reportService           = $reportService;
        $this->entityManager           = $entityManager;
        $this->translator              = $translator;
    }

    public function listAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->getQuery('clear') !== null) {
            return $this->redirect()->toRoute('zfcadmin/evaluation/report/list');
        }

        $page = $this->params()->fromRoute('page', 1);
        $filterPlugin = $this->getEvaluationFilter();
        $filterValues = $filterPlugin->getFilter();
        $subject = $filterValues['subject'] ?? null;
        $type = $filterValues['type'] ?? EvaluationReport::TYPE_INDIVIDUAL;
        $versionType = $filterValues['version'] ?? null;
        /** @var QueryBuilder $reportQuery */
        $reportQuery = $this->evaluationReportService->findFiltered(EvaluationReport::class, $filterValues);

        // Download presentation
        if (($request->getQuery('presentation') !== null) && ($type === EvaluationReport::TYPE_FINAL)) {
            $evaluationReports = [];
            foreach ($reportQuery->getQuery()->getResult() as $item) {
                if (($item instanceof ProjectVersion)
                    && ($item->getProjectVersionReport() instanceof ProjectVersionReport)
                ) {
                    $evaluationReports[] = $item->getProjectVersionReport()->getEvaluationReport();
                } elseif (($item instanceof ProjectReport)
                    && ($item->getProjectReportReport() instanceof ProjectReportReport)
                ) {
                    $evaluationReports[] = $item->getProjectReportReport()->getEvaluationReport();
                }
            }
            return $this->evaluationReportPresentation($evaluationReports)->parseResponse();
        }

        $paginator = new Paginator(new PaginatorAdapter(new ORMPaginator($reportQuery, false)));
        $paginator::setDefaultItemCountPerPage(($page === 'all') ? PHP_INT_MAX : 20);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange(ceil($paginator->getTotalItemCount() / $paginator::getDefaultItemCountPerPage()));

        $form = new ReportFilter($this->entityManager);
        $form->setData(['filter' => $filterValues]);

        if ($subject !== ReportType::TYPE_REPORT) {
            $arguments = $filterPlugin->parseFilteredSortQuery(['year', 'period']);
        } else {
            $arguments = $filterPlugin->parseFilteredSortQuery(['version']);
        }

        return new ViewModel([
            'subject'     => $subject,
            'versionType' => $versionType,
            'type'        => $type,
            'paginator'   => $paginator,
            'form'        => $form,
            'order'       => $filterPlugin->getOrder(),
            'direction'   => $filterPlugin->getDirection(),
            'arguments'   => $arguments
        ]);
    }

    public function newFinalAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $offlineMode = ($request->getQuery('mode') === 'offline');
        $versionId = $this->params()->fromRoute('version');
        $reportId = $this->params()->fromRoute('report');

        if (($versionId === null) && ($reportId === null)) {
            return $this->notFoundAction();
        }

        $type = ($versionId === null) ?
            (($reportId === null) ? null : ReportType::TYPE_GENERAL_REPORT)
            : ReportType::TYPE_GENERAL_VERSION;

        // Create upon starting the form so that create and edit can be handled by the same form
        $evaluationReport = new EvaluationReport();
        $report = null;
        $version = null;

        // In offline mode, produce an Excel download instead of the form
        if ($offlineMode) {
            switch ($type) {
                case ReportType::TYPE_GENERAL_REPORT:
                    /** @var ProjectReport $report */
                    $report = $this->reportService->find(ProjectReport::class, (int)$reportId);
                    $label = $report->parseName();
                    $projectReportReport = new EvaluationReport\ProjectReport();
                    $projectReportReport->setReport($report);
                    $projectReportReport->setEvaluationReport($evaluationReport);
                    $evaluationReport->setProjectReportReport($projectReportReport);
                    /** @var EvaluationReport\Version $reportVersion */
                    $reportVersion = $this->evaluationReportService->findReportVersionForProjectReport();
                    break;

                case ReportType::TYPE_GENERAL_VERSION:
                    /** @var ProjectVersion $version */
                    $version = $this->versionService->find(ProjectVersion::class, (int)$versionId);
                    $label = $version->getVersionType()->getDescription();
                    $projectVersionReport = new EvaluationReport\ProjectVersion();
                    $projectVersionReport->setVersion($version);
                    $projectVersionReport->setEvaluationReport($evaluationReport);
                    $evaluationReport->setProjectVersionReport($projectVersionReport);
                    /** @var EvaluationReport\Version $reportVersion */
                    $reportVersion = $this->evaluationReportService->findReportVersionForProjectVersion($version);
                    break;
                default:
                    return $this->notFoundAction();
            }

            $evaluationReport->setVersion($reportVersion);
            // Prepare the empty results for the criteria
            /** @var EvaluationReport\Criterion\Version $criterionVersion */
            foreach ($reportVersion->getCriterionVersions() as $criterionVersion) {
                $result = new ReportResult();
                if ($criterionVersion->getCriterion()->getHasScore()) {
                    $scoreValues = array_keys(ReportResult::getScoreValues());
                    $result->setScore(reset($scoreValues));
                }
                $result->setCriterionVersion($criterionVersion);
                $result->setEvaluationReport($evaluationReport);
                $evaluationReport->getResults()->add($result);
            }

            // Upload Excel
            if ($request->isPost()) {
                $data = array_merge($request->getPost()->toArray(), $request->getFiles()->toArray());
                $uploadForm = new ReportUpload(
                    ''
                ); //@bart, could the action be '' as you only use the form here for validation
                $uploadForm->setData($data);
                $excel = $uploadForm->get('excel')->getValue();
                if ($uploadForm->isValid() && !empty($excel['name']) && ($excel['error'] === 0)) {
                    // Version evaluation reports are automatically finalised
                    if ($evaluationReport->getProjectVersionReport() instanceof ProjectVersionReport) {
                        $evaluationReport->setFinal(true);
                    }
                    $success = false;
                    $importHelper = $this->evaluationReportExcelImport($excel['tmp_name']);
                    if (!$importHelper->hasParseErrors()) {
                        $success = $importHelper->import($evaluationReport);
                    }
                    unlink($excel['tmp_name']);
                    if ($success) {
                        $this->evaluationReportService->save($evaluationReport);
                        $this->flashMessenger()->addSuccessMessage(
                            sprintf(
                                $this->translator->translate(
                                    'txt-%s-final-evaluation-report-has-successfully-been-created'
                                ),
                                $label
                            )
                        );
                    } else {
                        $this->flashMessenger()->setNamespace('error')->addMessage(
                            sprintf(
                                $this->translator->translate('txt-failed-importing-evaluation-report-for-%s'),
                                $label
                            )
                        );
                    }
                }

                switch ($type) {
                    case ReportType::TYPE_GENERAL_REPORT:
                        return $this->redirect()->toRoute(
                            'zfcadmin/project/report/view',
                            ['id' => $report->getId()],
                            ['fragment' => 'evaluation']
                        );
                        break;
                    case ReportType::TYPE_GENERAL_VERSION:
                        return $this->redirect()->toRoute(
                            'zfcadmin/project/version/view',
                            ['id' => $version->getId()],
                            ['fragment' => 'evaluation']
                        );
                        break;
                }
            } // Final version can only be uploaded from an existing sheet
            else {
                return $this->notFoundAction();
            }
        }

        // Online mode not implemented yet
        return $this->notFoundAction();
    }

    public function editFinalAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $form = null;
        $offlineMode = ($request->getQuery('mode') === 'offline');

        /** @var EvaluationReport $evaluationReport */
        $evaluationReport = $this->evaluationReportService->find(
            EvaluationReport::class,
            (int)$this->params('id')
        );

        if ($evaluationReport === null) {
            return $this->notFoundAction();
        }

        // Upload offline Excel
        if ($offlineMode && $request->isPost()) {
            $data = array_merge($request->getPost()->toArray(), $request->getFiles()->toArray());
            $uploadForm = new ReportUpload('');//@bart, could the action be '' as you only use the form here for validation
            $uploadForm->setData($data);
            $excel = $uploadForm->get('excel')->getValue();
            if ($uploadForm->isValid() && !empty($excel['name']) && ($excel['error'] === 0)) {
                $success = false;
                $importHelper = $this->evaluationReportExcelImport($excel['tmp_name']);
                if (!$importHelper->hasParseErrors()) {
                    // Prevent duplicate entries by clearing old results when an outdated Excel is used
                    if ($importHelper->excelIsOutdated($evaluationReport)) {
                        $evaluationReport->getResults()->clear();
                        $this->evaluationReportService->save($evaluationReport);
                    }
                    $success = $importHelper->import($evaluationReport);
                }
                unlink($excel['tmp_name']);
                if ($success) {
                    $this->evaluationReportService->save($evaluationReport);
                    $this->flashMessenger()->addSuccessMessage(
                        sprintf(
                            $this->translator->translate('txt-%s-evaluation-report-has-successfully-been-updated'),
                            EvaluationReportService::parseLabel($evaluationReport)
                        )
                    );
                } else {
                    $this->flashMessenger()->setNamespace('error')->addMessage(
                        sprintf(
                            $this->translator->translate('txt-failed-importing-evaluation-report-for-%s'),
                            EvaluationReportService::parseLabel($evaluationReport)
                        )
                    );
                }
            }

            if ($evaluationReport->getProjectReportReport() instanceof ProjectReportReport) {
                return $this->redirect()->toRoute(
                    'zfcadmin/project/report/view',
                    ['id' => $evaluationReport->getProjectReportReport()->getReport()->getId()],
                    ['fragment' => 'evaluation']
                );
            }

            return $this->redirect()->toRoute(
                'zfcadmin/project/version/view',
                ['id' => $evaluationReport->getProjectVersionReport()->getVersion()->getId()],
                ['fragment' => 'evaluation']
            );
        }

        // Online mode not implemented yet
        return $this->notFoundAction();
    }

    public function downloadAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $format = $request->getQuery('format');
        /** @var EvaluationReport $evaluationReport */
        $evaluationReport = $this->evaluationReportService->find(
            EvaluationReport::class,
            (int)$this->params('id')
        );
        if ($evaluationReport === null) {
            return $this->notFoundAction();
        }
        switch ($format) {
            case 'distributable':
                return $this->evaluationReportExcelExport($evaluationReport, true, true)
                    ->parseResponse();
                break;
            case 'pdf':
                return $this->evaluationReportPdfExport($evaluationReport)->parseResponse();
                break;
            case 'distributable-pdf':
                return $this->evaluationReportPdfExport($evaluationReport, true)->parseResponse();
                break;
            case 'consolidated-pdf':
                return $this->evaluationConsolidatedPdfExport($evaluationReport)->parseResponse();
                break;
            default:
                return $this->evaluationReportExcelExport($evaluationReport, true)->parseResponse();
        }
    }

    public function finaliseAction()
    {
        /** @var EvaluationReport $evaluationReport */
        $evaluationReport = $this->evaluationReportService->find(
            EvaluationReport::class,
            (int)$this->params('id')
        );

        if ($evaluationReport === null) {
            return $this->notFoundAction();
        }

        $label = EvaluationReportService::parseLabel($evaluationReport);
        $evaluationReport->setFinal(true);
        $this->evaluationReportService->save($evaluationReport);

        $this->flashMessenger()->addSuccessMessage(
            sprintf(
                $this->translator->translate('txt-evaluation-report-for-%s-successfully-finalised'),
                $label
            )
        );

        if ($evaluationReport->getProjectReportReport() instanceof ProjectReportReport) {
            return $this->redirect()->toRoute(
                'zfcadmin/project/report/view',
                ['id' => $evaluationReport->getProjectReportReport()->getReport()->getId()],
                ['fragment' => 'evaluation']
            );
        }

        return $this->redirect()->toRoute(
            'zfcadmin/project/version/view',
            ['id' => $evaluationReport->getProjectVersionReport()->getVersion()->getId()],
            ['fragment' => 'evaluation']
        );
    }

    public function undoFinalAction()
    {
        /** @var EvaluationReport $evaluationReport */
        $evaluationReport = $this->evaluationReportService->find(
            EvaluationReport::class,
            (int)$this->params('id')
        );

        if ($evaluationReport === null) {
            return $this->notFoundAction();
        }

        $evaluationReport->setFinal(false);
        $this->evaluationReportService->save($evaluationReport);

        $this->flashMessenger()->addSuccessMessage(
            $this->translator->translate('txt-evaluation-report-is-no-longer-final')
        );

        // Final PPR evaluation report
        if ($evaluationReport->getProjectReportReport() instanceof ProjectReportReport) {
            return $this->redirect()->toRoute(
                'zfcadmin/project/report/view',
                ['id' => $evaluationReport->getProjectReportReport()->getReport()->getId()],
                ['fragment' => 'evaluation']
            );
        }
        // Final PO/FPP/CR evaluation report
        if ($evaluationReport->getProjectVersionReport() instanceof ProjectVersionReport) {
            return $this->redirect()->toRoute(
                'zfcadmin/project/version/view',
                ['id' => $evaluationReport->getProjectVersionReport()->getVersion()->getId()],
                ['fragment' => 'evaluation']
            );
        }

        // Individual evaluation report
        return $this->redirect()->toRoute(
            'community/evaluation/report/view',
            ['id' => $evaluationReport->getId()]
        );
    }

    public function migrateAction()
    {
        $log = $this->evaluationReportService->migrate();
        echo implode('<br>', $log);
        die();
    }
}
