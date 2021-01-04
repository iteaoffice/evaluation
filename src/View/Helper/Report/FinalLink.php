<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\View\Helper\Report;

use Evaluation\Acl\Assertion\ReportAssertion;
use Evaluation\Entity\Report as EvaluationReport;
use General\ValueObject\Link\Link;
use General\ValueObject\Link\LinkDecoration;
use Project\Entity\Report\Report as ProjectReport;
use Project\Entity\Version\Version as ProjectVersion;

/**
 * Class FinalLink
 *
 * @package Evaluation\View\Helper\Report
 */
final class FinalLink extends \General\View\Helper\AbstractLink
{
    public function __invoke(
        EvaluationReport $evaluationReport = null,
        string $action = 'view',
        string $show = LinkDecoration::SHOW_TEXT,
        ProjectReport $projectReport = null,
        ProjectVersion $projectVersion = null
    ): string {
        $evaluationReport ??= new EvaluationReport();
        if (! $this->hasAccess($evaluationReport, ReportAssertion::class, $action)) {
            return '';
        }
        $routeParams = [];
        if (! $evaluationReport->isEmpty()) {
            $routeParams['id'] = $evaluationReport->getId();
        }
        switch ($action) {
            case 'download-offline-form':
                $route = 'zfcadmin/evaluation/report/list';
                $text  = '';
                if (null !== $projectReport) {
                    $route = 'zfcadmin/evaluation/report/create-from-report';
                    $text = $this->translator->translate('txt-download-offline-form');
                    $routeParams['report'] = $projectReport->getId();
                } elseif (null !== $projectVersion) {
                    $route = 'zfcadmin/evaluation/report/create-from-version';
                    $text = $this->translator->translate('txt-download-offline-form');
                    $routeParams['version'] = $projectVersion->getId();
                } elseif (null !== $evaluationReport) {
                    $text = $this->translator->translate('txt-download');
                    $route = 'zfcadmin/evaluation/report/update';
                }
                $linkParams = [
                    'route'       => $route,
                    'queryParams' => ['mode' => 'offline'],
                    'text'        => $text
                ];
                break;
            case 'finalise':
                $linkParams = [
                    'icon'  => 'fa-lock',
                    'route' => 'zfcadmin/evaluation/report/finalise',
                    'text'  => $this->translator->translate('txt-finalise-evaluation-report')
                ];
                break;
            case 'undo-final':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/undo-final',
                    'text'  => $this->translator->translate('txt-undo-finalisation')
                ];
                break;
            case 'download':
                $linkParams = [
                    'icon'  => 'far fa-file-excel',
                    'route' => 'zfcadmin/evaluation/report/download',
                    'text'  => $this->translator->translate('txt-download-original-version')
                ];
                break;
            case 'download-distributable':
                $linkParams = [
                    'icon'        => 'far fa-file-excel',
                    'route'       => 'zfcadmin/evaluation/report/download',
                    'queryParams' => ['format' => 'distributable'],
                    'text'        => $this->translator->translate('txt-download-distributable-version')
                ];
                break;
            case 'download-pdf':
                $linkParams = [
                    'icon'        => 'far fa-file-pdf',
                    'route'       => 'zfcadmin/evaluation/report/download',
                    'queryParams' => ['format' => 'pdf'],
                    'text'        => $this->translator->translate('txt-download-as-pdf')
                ];
                break;
            case 'download-distributable-pdf':
                $linkParams = [
                    'icon'        => 'far fa-file-pdf',
                    'route'       => 'zfcadmin/evaluation/report/download',
                    'queryParams' => ['format' => 'distributable-pdf'],
                    'text'        => $this->translator->translate('txt-download-distributable-version-as-pdf')
                ];
                break;
            case 'download-consolidated-pdf':
                $linkParams = [
                    'icon'        => 'far fa-file-pdf',
                    'route'       => 'zfcadmin/evaluation/report/download',
                    'queryParams' => ['format' => 'consolidated-pdf'],
                    'text'        => $this->translator->translate('txt-download-consolidated-version-as-pdf')
                ];
                break;
            default:
                return '';
        }
        $linkParams['action']      = $action;
        $linkParams['show']        = $show;
        $linkParams['routeParams'] = $routeParams;
        return $this->parse(Link::fromArray($linkParams));
    }
}
