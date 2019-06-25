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
        string $action = 'view',
        string $show = 'text',
        ProjectReport $projectReport = null,
        ProjectVersion $projectVersion = null
    ): string {
        $this->reset();

        $this->extractRouterParams($evaluationReport, ['id']);

        if (!$this->hasAccess($evaluationReport ?? new EvaluationReport(), ReportAssertion::class, $action)) {
            return '';
        }

        $this->parseAction($action, $evaluationReport, $projectReport, $projectVersion);

        return $this->createLink($show);
    }

    public function parseAction(
        string $action,
        ?EvaluationReport $evaluationReport,
        ?ProjectReport $projectReport,
        ?ProjectVersion $version
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
                $this->setRouter($route);
                $this->addQueryParam('mode', 'offline');
                break;
            case 'finalise':
                $this->setRouter('zfcadmin/evaluation/report/finalise');
                $this->setText($this->translator->translate('txt-finalise-evaluation-report'));
                break;
            case 'undo-final':
                $this->setRouter('zfcadmin/evaluation/report/undo-final');
                $this->setText($this->translator->translate('txt-undo-finalisation'));
                break;
            case 'download':
                $this->setRouter('zfcadmin/evaluation/report/download');
                $this->setText($this->translator->translate('txt-download-original-version'));
                break;
            case 'download-distributable':
                $this->setRouter('zfcadmin/evaluation/report/download');
                $this->addQueryParam('format', 'distributable');
                $this->setText($this->translator->translate('txt-download-distributable-version'));
                break;
            case 'download-pdf':
                $this->setRouter('zfcadmin/evaluation/report/download');
                $this->addQueryParam('format', 'pdf');
                $this->setText($this->translator->translate('txt-download-as-pdf'));
                break;
            case 'download-distributable-pdf':
                $this->setRouter('zfcadmin/evaluation/report/download');
                $this->addQueryParam('format', 'distributable-pdf');
                $this->setText($this->translator->translate('txt-download-distributable-version-as-pdf'));
                break;
        }
    }
}
