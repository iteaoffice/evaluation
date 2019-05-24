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

namespace Evaluation\View\Helper;

use Project\Acl\Assertion\Evaluation\Report2 as EvaluationReportAssertion;
use Project\Entity\Evaluation\Report2 as EvaluationReport;
use Project\Entity\Report\Review as ReportReview;
use Project\Entity\Version\Review as VersionReview;
use Project\Service\EvaluationReport2Service as EvaluationReportService;
use Project\View\Helper\LinkAbstract;
use function sprintf;

/**
 * Class Report2Link
 * @package Project\View\Helper\Evaluation
 */
final class ReportLink extends LinkAbstract
{
    /**
     * @var EvaluationReport
     */
    protected $evaluationReport;

    /**
     * @var ReportReview
     */
    private $reportReview;

    /**
     * @var VersionReview
     */
    private $versionReview;

    /**
     * @var EvaluationReportService
     */
    private $evaluationReportService;

    /**
     * @var bool
     */
    private $shortLabel = false;

    public function __invoke(
        EvaluationReport $evaluationReport = null,
        string           $action = 'view',
        string           $show = 'text',
        bool             $shortLabel = false,
        ReportReview     $reportReview = null,
        VersionReview    $versionReview = null
    ): string {
        $this->evaluationReport = $evaluationReport ?? new EvaluationReport();
        $this->shortLabel       = $shortLabel;
        $this->reportReview     = $reportReview ?? new ReportReview();
        $this->versionReview    = $versionReview ?? new VersionReview();
        $this->setAction($action);
        $this->setShow($show);

        if (!$this->evaluationReport->isEmpty()) {
            $this->addRouterParam('id', $this->evaluationReport->getId());
        }

        if (!$this->hasAccess($this->evaluationReport, EvaluationReportAssertion::class, $this->getAction())) {
            return '';
        }

        return $this->createLink();
    }

    /**
     * @throws \Exception
     * @return void
     */
    public function parseAction(): void
    {
        switch ($this->getAction()) {
            case 'overview':
                $this->setRouter('community/evaluation/report2/list');
                $this->setShowOptions([
                    'notification' => $this->translator->translate('txt-new-project-evaluations-pending')
                ]);
                break;
            case 'new':
            case 'new-list':
                $route = 'community';
                $subject = '';
                if (!$this->reportReview->isEmpty()) {
                    $route = 'community/evaluation/report2/create-from-report-review';
                    $subject = sprintf(
                        '%s - %s - %s',
                        $this->reportReview->getProjectReport()->getProject()->getCall(),
                        $this->reportReview->getProjectReport()->getProject()->parseFullName(),
                        $this->reportReview->getProjectReport()->parseName()
                    );
                    $this->addRouterParam('reportReview', $this->reportReview->getId());
                } elseif (!$this->versionReview->isEmpty()) {
                    $route = 'community/evaluation/report2/create-from-version-review';
                    $subject = sprintf(
                        '%s - %s - %s',
                        $this->versionReview->getVersion()->getProject()->getCall(),
                        $this->versionReview->getVersion()->getProject()->parseFullName(),
                        $this->versionReview->getVersion()->getVersionType()
                    );
                    $this->addRouterParam('versionReview', $this->versionReview->getId());
                }
                $this->setRouter($route);
                $fullLabel = sprintf($this->translator->translate('txt-create-evaluation-report-for-%s'), $subject);
                $this->setText($fullLabel);
                if ($this->shortLabel) {
                    $this->setShowOptions(['name' => $subject]);
                } else {
                    $this->setShowOptions(['name' => $fullLabel]);
                }
                $this->setFragment('offline');
                break;
            case 'download-offline-form':
                $route = 'community';
                if (!$this->reportReview->isEmpty()) {
                    $route = 'community/evaluation/report2/create-from-report-review';
                    $this->addRouterParam('reportReview', $this->reportReview->getId());
                } elseif (!$this->versionReview->isEmpty()) {
                    $route = 'community/evaluation/report2/create-from-version-review';
                    $this->addRouterParam('versionReview', $this->versionReview->getId());
                } elseif (!$this->evaluationReport->isEmpty()) {
                    $route = 'community/evaluation/report2/update';
                }

                $this->setRouter($route);
                $this->setQuery(['mode' => 'offline']);
                $this->setText($this->translator->translate('txt-download-offline-form'));
                break;
            case 'view':
                $this->setRouter('community/evaluation/report2/view');
                if ($this->shortLabel) {
                    $label = $this->getEvaluationReportService()->parseLabel($this->evaluationReport, '%3$s');
                } else {
                    $label = $this->getEvaluationReportService()->parseLabel($this->evaluationReport);
                }
                $this->setText(sprintf($this->translator->translate('txt-view-evaluation-report-for-%s'), $label));
                $this->setShowOptions(['name' => $label]);
                break;
            case 'edit':
                $this->setRouter('community/evaluation/report2/update');
                if ($this->shortLabel) {
                    $label = $this->getEvaluationReportService()->parseLabel($this->evaluationReport, '%3$s');
                } else {
                    $label = $this->getEvaluationReportService()->parseLabel($this->evaluationReport);
                }
                $this->setText(sprintf($this->translator->translate('txt-update-evaluation-report-for-%s'), $label));
                $this->setShowOptions(['name' => $label]);
                break;
            case 'finalise':
                $this->setRouter('community/evaluation/report2/finalise');
                $this->setText($this->translator->translate('txt-finalise-evaluation-report'));
                break;
            case 'undo-final':
                $this->setRouter('zfcadmin/evaluation/report2/undo-final');
                $this->setText($this->translator->translate('txt-undo-finalisation'));
                break;
            default:
                throw new \Exception(sprintf("%s is an incorrect action for %s", $this->getAction(), __CLASS__));
        }
    }

    /**
     * @return EvaluationReportService
     */
    protected function getEvaluationReportService()
    {
        if ($this->evaluationReportService === null) {
            $this->evaluationReportService = $this->getServiceManager()->get(EvaluationReportService::class);
        }

        return $this->evaluationReportService;
    }
}
