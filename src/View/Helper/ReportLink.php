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

use Evaluation\Acl\Assertion\ReportAssertion;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Service\EvaluationReportService;
use Project\Entity\Report\Reviewer as ReportReviewer;
use Project\Entity\Version\Reviewer as VersionReviewer;
use function sprintf;

/**
 * Class ReportLink
 *
 * @package Evaluation\View\Helper
 */
final class ReportLink extends AbstractLink
{
    public function __invoke(
        EvaluationReport $evaluationReport = null,
        string $action = 'view',
        string $show = 'text',
        bool $shortLabel = false,
        ReportReviewer $reportReview = null,
        VersionReviewer $versionReview = null
    ): string {
        $this->reset();

        $this->extractRouterParams($evaluationReport, ['id']);

        if (!$this->hasAccess($evaluationReport ?? new EvaluationReport(), ReportAssertion::class, $action)) {
            return '';
        }

        $this->parseAction($action, $shortLabel, $evaluationReport, $reportReview, $versionReview);

        return $this->createLink($show);
    }

    public function parseAction(
        string $action,
        bool $shortLabel,
        ?EvaluationReport $evaluationReport,
        ?ReportReviewer $reportReview,
        ?VersionReviewer $versionReview
    ): void {
        $this->action = $action;

        switch ($$action) {
            case 'overview':
                $this->setRouter('community/evaluation/report/list');
                $this->addShowOption(
                    'notification',
                    $this->translator->translate('txt-new-project-evaluations-pending')
                );
                break;
            case 'new':
            case 'new-list':
                $route = 'community';
                $subject = '';
                if (null !== $reportReview) {
                    $route = 'community/evaluation/report/create-from-report-review';
                    $subject = sprintf(
                        '%s - %s - %s',
                        $reportReview->getProjectReport()->getProject()->getCall(),
                        $reportReview->getProjectReport()->getProject()->parseFullName(),
                        $reportReview->getProjectReport()->parseName()
                    );
                    $this->addRouteParam('reportReviewer', $reportReview->getId());
                } elseif (null !== $versionReview) {
                    $route = 'community/evaluation/report/create-from-version-review';
                    $subject = sprintf(
                        '%s - %s - %s',
                        $versionReview->getVersion()->getProject()->getCall(),
                        $versionReview->getVersion()->getProject()->parseFullName(),
                        $versionReview->getVersion()->getVersionType()
                    );
                    $this->addRouteParam('versionReviewer', $versionReview->getId());
                }
                $this->setRouter($route);
                $fullLabel = sprintf($this->translator->translate('txt-create-evaluation-report-for-%s'), $subject);
                $this->setText($fullLabel);
                if ($shortLabel) {
                    $this->addShowOption('name', $subject);
                } else {
                    $this->addShowOption('name', $fullLabel);
                }
                break;
            case 'download-offline-form':
                $route = 'community';
                if (null !== $reportReview) {
                    $route = 'community/evaluation/report/create-from-report-review';
                    $this->addRouteParam('reportReviewer', $reportReview->getId());
                } elseif (null !== $versionReview) {
                    $route = 'community/evaluation/report/create-from-version-review';
                    $this->addRouteParam('versionReviewer', $versionReview->getId());
                } elseif (null !== $evaluationReport) {
                    $route = 'community/evaluation/report/update';
                }

                $this->setRouter($route);
                $this->addQueryParam('mode', 'offline');
                $this->setText($this->translator->translate('txt-download-offline-form'));
                break;
            case 'view':
                $this->setRouter('community/evaluation/report/view');
                if ($shortLabel) {
                    $label = EvaluationReportService::parseLabel($evaluationReport, '%3$s');
                } else {
                    $label = EvaluationReportService::parseLabel($evaluationReport);
                }
                $this->setText(sprintf($this->translator->translate('txt-view-evaluation-report-for-%s'), $label));
                $this->addShowOption('name', $label);
                break;
            case 'edit':
                $this->setRouter('community/evaluation/report/update');
                if ($shortLabel) {
                    $label = EvaluationReportService::parseLabel($evaluationReport, '%3$s');
                } else {
                    $label = EvaluationReportService::parseLabel($evaluationReport);
                }
                $this->setText(sprintf($this->translator->translate('txt-update-evaluation-report-for-%s'), $label));
                $this->addShowOption('name', $label);
                break;
            case 'finalise':
                $this->setRouter('community/evaluation/report/finalise');
                $this->setText($this->translator->translate('txt-finalise-evaluation-report'));
                break;
            case 'undo-final':
                $this->setRouter('zfcadmin/evaluation/report/undo-final');
                $this->setText($this->translator->translate('txt-undo-finalisation'));
                break;
        }
    }
}
