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

use Contact\Entity\Contact;
use DateTime;
use Doctrine\ORM\EntityManager;
use Evaluation\Controller\Plugin\Report\ExcelDownload;
use Evaluation\Controller\Plugin\Report\ExcelExport;
use Evaluation\Controller\Plugin\Report\ExcelImport;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Entity\Report\Result;
use Evaluation\Entity\Report\Type as EvaluationReportType;
use Evaluation\Form\Report as EvaluationReportForm;
use Evaluation\Form\ReportUpload;
use Evaluation\Service\EvaluationReportService;
use Project\Service\ProjectService;
use Project\Entity\Version\Review as VersionReview;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Zend\Mvc\Plugin\Identity\Identity;
use Zend\View\Model\ViewModel;
use function array_merge;
use function sprintf;
use function unlink;

/**
 * Project evaluation report controller.
 *
 * @method Identity|Contact identity()
 * @method FlashMessenger flashMessenger()
 * @method ExcelExport evaluationReportExcelExport(EvaluationReport $evaluationReport, bool $isFinal = false, bool $forDistribution = false)
 * @method ExcelImport evaluationReportExcelImport(string $file)
 * @method ExcelDownload evaluationReportExcelDownload(Contact $contact, int $status)
 *
 */
final class ReportController extends AbstractActionController
{
    /**
     * @var EvaluationReportService
     */
    private $evaluationReportService;
    /**
     * @var ProjectService
     */
    private $projectService;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(
        EvaluationReportService $evaluationReportService,
        ProjectService          $projectService,
        TranslatorInterface     $translator,
        EntityManager           $entityManager
    ) {
        $this->evaluationReportService = $evaluationReportService;
        $this->projectService          = $projectService;
        $this->translator              = $translator;
        $this->entityManager           = $entityManager;
    }

    public function listAction()
    {
        $reportsNew = $this->evaluationReportService->findReviewReportsByContact(
            $this->identity(),
            EvaluationReportService::STATUS_NEW
        );
        $hasNew = false;
        foreach ($reportsNew as $container) {
            if (!empty($container['reviews'])) {
                $hasNew = true;
                break;
            }
        }

        /*$reportsInProgress = $this->evaluationReportService->findReviewReportsByContact(
            $this->identity(),
            EvaluationReportService::STATUS_IN_PROGRESS
        );
        $hasInProgress = false;
        foreach ($reportsInProgress as $container) {
            if (!empty($container['reviews'])) {
                $hasInProgress = true;
                break;
            }
        }

        $reportsFinal = $this->evaluationReportService->findReviewReportsByContact(
            $this->identity(),
            EvaluationReportService::STATUS_FINAL
        );
        $hasFinal = !empty($reportsFinal);*/

        return new ViewModel(
            [
                'reportsNew'              => $reportsNew,
                'hasNew'                  => $hasNew,
                //'reportsInProgress'       => $reportsInProgress,
                //'hasInProgress'           => $hasInProgress,
                //'reportsFinal'            => $reportsFinal,
                //'hasFinal'                => $hasFinal,
                'evaluationReportService' => $this->evaluationReportService
            ]
        );
    }

    public function viewAction(): ViewModel
    {
        /** @var EvaluationReport $evaluationReport */
        $evaluationReport = $this->evaluationReportService->find(
            EvaluationReport::class,
            (int)$this->params('id')
        );

        if ($evaluationReport === null) {
            return $this->notFoundAction();
        }

        $projectVersionReport = $evaluationReport->getProjectVersionReport();
        $projectReportReport = $evaluationReport->getProjectReportReport();
        if ($projectVersionReport !== null) {
            $type      = EvaluationReportType::TYPE_GENERAL_VERSION;
            $reviewer  = $projectVersionReport->getReviewer();
            $reviewers = $reviewer->getVersion()->getVersionReview();
            $project   = $reviewer->getVersion()->getProject();
            $label     = $reviewer->getVersion()->getVersionType()->getDescription();
        } elseif ($projectReportReport !== null) {
            $type      = EvaluationReportType::TYPE_GENERAL_REPORT;
            $reviewer  = $projectReportReport->getReviewer();
            $reviewers = $reviewer->getProjectReport()->getReview();
            $project   = $reviewer->getProjectReport()->getProject();
            $label     = $reviewer->getProjectReport()->parseName();
        } else {
            return $this->notFoundAction();
        }

        $percentageComplete = $this->evaluationReportService->parseCompletedPercentage($evaluationReport);

        $uploadFormAction = $this->url()->fromRoute(
            'community/evaluation/report/update',
            ['id' => $evaluationReport->getId()],
            ['query' => ['mode' => 'offline'], 'fragment' => 'offline']
        );

        $uploadForm = new ReportUpload($uploadFormAction);

        return new ViewModel([
            'label'          => $label,
            'projectService' => $this->projectService,
            'project'        => $project,
            'type'           => $type,
            'review'         => $reviewer,
            'reviewers'      => $reviewers,
            'report'         => $evaluationReport,
            'results'        => $this->evaluationReportService->getSortedResults($evaluationReport),
            'scoreValues'    => Result::getScoreValues(),
            'complete'       => $percentageComplete === (float)100,
            'uploadForm'     => $uploadForm
        ]);
    }

    public function newAction()
    {
        /** @var Request $request */
        $request         = $this->getRequest();
        $offlineMode     = ($request->getQuery('mode') === 'offline');
        $versionReviewId = $this->params()->fromRoute('versionReview');
        $reportReviewId  = $this->params()->fromRoute('reportReview');
        $reviewId        = ($versionReviewId === null) ? $reportReviewId : $versionReviewId;

        if (($versionReviewId === null) && ($reportReviewId === null)) {
            return $this->notFoundAction();
        }

        // Check for non-archived evaluation report versions and use the most recent when there are multiple
        if ($versionReviewId !== null) {
            /** @var VersionReview $versionReview */
            $versionReview = $this->evaluationReportService->find(VersionReview::class, (int) $reviewId);
            if ($versionReview instanceof VersionReview) {
                $reportVersion = $this->evaluationReportService
                    ->findReportVersionForProjectVersion($versionReview->getVersion());
            } else {
                return $this->notFoundAction();
            }
        } else {
            $reportVersion = $this->evaluationReportService->findReportVersionForProjectReport();
        }

        if ($reportVersion === null) {
            $this->flashMessenger()->addErrorMessage(
                $this->translator->translate('txt-no-active-evaluation-report-template-found')
            );
            return $this->redirect()->toRoute('community/evaluation/report2/list');
        }

        // Create an evaluation report so that new and edit can be handled by the same form
        $evaluationReport = $this->evaluationReportService->prepareEvaluationReport($reportVersion, (int)$reviewId);
        $label            = $this->evaluationReportService->parseLabel($evaluationReport);
        $reviewers        = $this->evaluationReportService->getReviewers($evaluationReport);
        $project          = $this->evaluationReportService->getProject($evaluationReport);
        $reportReview     = ($evaluationReport->getProjectReportReport() !== null)
            ? $evaluationReport->getProjectReportReport()->getReviewer() : null;
        $versionReview    = ($evaluationReport->getProjectVersionReport() !== null)
            ? $evaluationReport->getProjectVersionReport()->getReviewer() : null;

        // Pre-fill FPP form with PO data
        $evaluationReportType = $evaluationReport->getVersion()->getReportType();
        if (!$request->isPost() && ($evaluationReportType === EvaluationReport\Type::TYPE_FPP_VERSION)) {
            $this->evaluationReportService->preFillFppReport($evaluationReport);
        }

        $uploadFormAction = $this->url()->fromRoute(
            $this->getEvent()->getRouteMatch()->getMatchedRouteName(),
            (isset($reportReview) ? ['reportReview' => $reportReview->getId()]
                : ['versionReview' => $versionReview->getId()]),
            ['query' => ['mode' => 'offline'], 'fragment' => 'offline']
        );

        $uploadForm = new ReportUpload($uploadFormAction);

        // In offline mode, produce an Excel download instead of the form
        if ($offlineMode) {
            // Upload Excel
            if ($request->isPost()) {
                $data = array_merge($request->getPost()->toArray(), $request->getFiles()->toArray());
                $uploadForm->setData($data);
                $excel = $uploadForm->get('excel')->getValue();
                if ($uploadForm->isValid() && !empty($excel['name']) && ($excel['error'] === 0)) {
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
                                $this->translator->translate('txt-%s-evaluation-report-has-successfully-been-created'),
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

                    return $this->redirect()->toRoute(
                        'community/evaluation/report2/view',
                        ['id' => $evaluationReport->getId()],
                        ['fragment' => 'report']
                    );
                } else {
                    $this->flashMessenger()->setNamespace('error')->addMessage(
                        sprintf(
                            $this->translator->translate('txt-please-provide-a-valid-excel-file'),
                            $label
                        )
                    );

                    return $this->redirect()->toRoute('community/evaluation/report2/list');
                }
            } // Download excel
            else {
                return $this->evaluationReportExcelExport($evaluationReport)->parseResponse();
            }
        }

        $form = new EvaluationReportForm($evaluationReport, $this->evaluationReportService, $this->entityManager);

        if ($request->isPost() && !$offlineMode) {
            $data = $request->getPost()->toArray();

            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute('community/evaluation/report2/list');
            }

            $form->setData($data);
            if ($form->isValid()) {
                /** @var EvaluationReport $evaluationReport */
                $evaluationReport = $form->getData();
                $this->evaluationReportService->save($evaluationReport);

                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('txt-%s-evaluation-report-has-successfully-been-created'),
                        $label
                    )
                );

                return $this->redirect()->toRoute(
                    'community/evaluation/report2/view',
                    ['id' => $evaluationReport->getId()],
                    ['fragment' => 'report']
                );
            }
        }

        return new ViewModel([
            'projectService' => $this->projectService,
            'review'         => $reportReview ?? $versionReview,
            //'type'           => $type,
            'project'        => $project,
            'reviewers'      => $reviewers,
            'form'           => $form,
            'uploadForm'     => $uploadForm,
            'report'         => $evaluationReport,
        ]);
    }

    public function editAction()
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

        // Final reports can't be edited any more in online mode
        if (!$offlineMode && $evaluationReport->getFinal()) {
            $this->flashMessenger()->addSuccessMessage(
                $this->translator->translate('txt-evaluation-report-is-final-and-cant-be-edited-any-more')
            );

            return $this->redirect()->toRoute('community/evaluation/report2/view', ['id' => $evaluationReport->getId()]);
        }

        $projectVersionReport = $evaluationReport->getProjectVersionReport();
        $projectReportReport = $evaluationReport->getProjectReportReport();
        if ($projectVersionReport !== null) {
            $type      = EvaluationReportType::TYPE_GENERAL_VERSION;
            $reviewer  = $projectVersionReport->getReviewer();
            $reviewers = $reviewer->getVersion()->getVersionReview();
            $project   = $reviewer->getVersion()->getProject();
            $label     = $reviewer->getVersion()->getVersionType()->getDescription();
        } elseif ($projectReportReport !== null) {
            $type      = EvaluationReportType::TYPE_GENERAL_REPORT;
            $reviewer  = $projectReportReport->getReviewer();
            $reviewers = $reviewer->getProjectReport()->getReview();
            $project   = $reviewer->getProjectReport()->getProject();
            $label     = $reviewer->getProjectReport()->parseName();
        } else {
            return $this->notFoundAction();
        }

        $uploadFormAction = $this->url()->fromRoute(
            $this->getEvent()->getRouteMatch()->getMatchedRouteName(),
            ['id' => $evaluationReport->getId()],
            ['query' => ['mode' => 'offline'], 'fragment' => 'offline']
        );

        $uploadForm = new ReportUpload($uploadFormAction);

        // In offline mode, produce an Excel download instead of the form
        if ($offlineMode) {
            // Upload Excel
            if ($request->isPost()) {
                $data = array_merge($request->getPost()->toArray(), $request->getFiles()->toArray());
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

                    return $this->redirect()->toRoute(
                        'community/evaluation/report2/view',
                        ['id' => $evaluationReport->getId()],
                        ['fragment' => 'report']
                    );
                } else {
                    $this->flashMessenger()->setNamespace('error')->addMessage(
                        sprintf(
                            $this->translator->translate('txt-please-provide-a-valid-excel-file'),
                            $label
                        )
                    );

                    return $this->redirect()->toRoute(
                        'community/evaluation/report2/update',
                        ['id' => $evaluationReport->getId()],
                        ['fragment' => 'offline']
                    );
                }
            } // Download excel
            else {
                return $this->evaluationReportExcelExport($evaluationReport)->parseResponse();
            }
        }

        $form = new EvaluationReportForm($evaluationReport, $this->evaluationReportService, $this->entityManager);

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute(
                    'community/evaluation/report2/view',
                    ['id' => $evaluationReport->getId()]
                );
            }

            $form->setData($data);
            if ($form->isValid()) {
                /** @var EvaluationReport $report */
                $report = $form->getData();
                $report->setDateUpdated(new DateTime());
                $this->evaluationReportService->save($report);

                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('txt-%s-evaluation-report-has-successfully-been-updated'),
                        $label
                    )
                );

                return $this->redirect()->toRoute(
                    'community/evaluation/report2/view',
                    ['id' => $report->getId()],
                    ['fragment' => 'report']
                );
            }
        }

        return new ViewModel([
            'projectService' => $this->projectService,
            'project'        => $project,
            'type'           => $type,
            'review'         => $reviewer,
            'reviewers'      => $reviewers,
            'form'           => $form,
            'uploadForm'     => $uploadForm,
            'label'          => $label,
            'report'         => $evaluationReport,
        ]);
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

        $percentageComplete = $this->evaluationReportService->parseCompletedPercentage($evaluationReport);
        $label = $this->evaluationReportService->parseLabel($evaluationReport);

        if ($percentageComplete === (float)100) {
            $evaluationReport->setFinal(true);
            $this->evaluationReportService->save($evaluationReport);

            $this->flashMessenger()->addSuccessMessage(
                sprintf(
                    $this->translator->translate('txt-evaluation-report-for-%s-successfully-finalised'),
                    $label
                )
            );
        } else {
            $this->flashMessenger()->addErrorMessage(
                sprintf(
                    $this->translator->translate('txt-evaluation-report-for-%s-is-only-%d%%-completed'),
                    $label,
                    $percentageComplete
                )
            );
        }

        return $this->redirect()->toRoute(
            'community/evaluation/report/view',
            ['id' => $evaluationReport->getId()]
        );
    }

    public function downloadCombinedAction(): Response
    {
        return $this->evaluationReportExcelDownload(
            $this->identity(),
            (int)$this->params('status', EvaluationReportService::STATUS_NEW)
        )->parseResponse();
    }
}
