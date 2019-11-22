<?php

/**
*
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2019 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\View\Helper\Report;

use Evaluation\Acl\Assertion\ReportAssertion;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\View\Helper\AbstractLink;
use Project\Entity\Report\Report as ProjectReport;
use Project\Entity\Version\Version as ProjectVersion;

/**
 * Class FinalLink
 *
 * @package Evaluation\View\Helper\Report
 */
final class FinalLink extends AbstractLink
{
    public function __invoke(
        EvaluationReport $evaluationReport = null,
        string           $action = 'view',
        string           $show = 'text',
        ProjectReport    $projectReport = null,
        ProjectVersion   $projectVersion = null
    ): string {

        $this->extractRouteParams($evaluationReport, ['id']);

        if (!$this->hasAccess($evaluationReport ?? new EvaluationReport(), ReportAssertion::class, $action)) {
            return '';
        }

        $this->parseAction($action, $evaluationReport, $projectReport, $projectVersion);

        return $this->createLink($show);
    }

    public function parseAction(
        string            $action,
        ?EvaluationReport $evaluationReport,
        ?ProjectReport    $projectReport,
        ?ProjectVersion   $version
    ): void {
        $this->action = $action;

        switch ($action) {
            case 'download-offline-form':
                $route = 'zfcadmin/evaluation/report/list';
                if (null !== $projectReport) {
                    $route = 'zfcadmin/evaluation/report/create-from-report';
                    $this->setText($this->translator->translate('txt-download-offline-form'));
                    $this->addRouteParam('report', $projectReport->getId());
                } elseif (null !== $version) {
                    $route = 'zfcadmin/evaluation/report/create-from-version';
                    $this->setText($this->translator->translate('txt-download-offline-form'));
                    $this->addRouteParam('version', $version->getId());
                } elseif (null !== $evaluationReport) {
                    $this->setText($this->translator->translate('txt-download'));
                    $route = 'zfcadmin/evaluation/report/update';
                }
                $this->setRoute($route);
                $this->addQueryParam('mode', 'offline');
                break;
            case 'finalise':
                $this->setRoute('zfcadmin/evaluation/report/finalise');
                $this->setText($this->translator->translate('txt-finalise-evaluation-report'));
                $this->setLinkIcon('fa fa-lock');
                break;
            case 'undo-final':
                $this->setRoute('zfcadmin/evaluation/report/undo-final');
                $this->setText($this->translator->translate('txt-undo-finalisation'));
                break;
            case 'download':
                $this->setRoute('zfcadmin/evaluation/report/download');
                $this->setText($this->translator->translate('txt-download-original-version'));
                $this->setLinkIcon('fa-file-excel-o');
                break;
            case 'download-distributable':
                $this->setRoute('zfcadmin/evaluation/report/download');
                $this->addQueryParam('format', 'distributable');
                $this->setText($this->translator->translate('txt-download-distributable-version'));
                $this->setLinkIcon('fa-file-excel-o');
                break;
            case 'download-pdf':
                $this->setRoute('zfcadmin/evaluation/report/download');
                $this->addQueryParam('format', 'pdf');
                $this->setText($this->translator->translate('txt-download-as-pdf'));
                $this->setLinkIcon('fa-file-pdf-o');
                break;
            case 'download-distributable-pdf':
                $this->setRoute('zfcadmin/evaluation/report/download');
                $this->addQueryParam('format', 'distributable-pdf');
                $this->setText($this->translator->translate('txt-download-distributable-version-as-pdf'));
                $this->setLinkIcon('fa-file-pdf-o');
                break;
            case 'download-consolidated-pdf':
                $this->setRoute('zfcadmin/evaluation/report/download');
                $this->addQueryParam('format', 'consolidated-pdf');
                $this->setText($this->translator->translate('txt-download-consolidated-version-as-pdf'));
                $this->setLinkIcon('fa-file-pdf-o');
                break;
        }
    }
}
