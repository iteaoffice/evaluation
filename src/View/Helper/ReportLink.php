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

namespace Evaluation\View\Helper;

use Evaluation\Acl\Assertion\ReportAssertion;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Service\EvaluationReportService;
use General\ValueObject\Link\Link;
use General\ValueObject\Link\LinkDecoration;
use Project\Entity\Report\Reviewer as ReportReviewer;
use Project\Entity\Version\Reviewer as VersionReviewer;
use function sprintf;

/**
 * Class ReportLink
 *
 * @package Evaluation\View\Helper
 */
final class ReportLink extends \General\View\Helper\AbstractLink
{
    public function __invoke(
        EvaluationReport $evaluationReport = null,
        string           $action = 'view',
        string           $show = LinkDecoration::SHOW_TEXT,
        bool             $shortLabel = false,
        ReportReviewer   $reportReviewer = null,
        VersionReviewer  $versionReviewer = null
    ): string
    {
        $evaluationReport ??= new EvaluationReport();

        if (!$this->hasAccess($evaluationReport, ReportAssertion::class, $action)) {
            return '';
        }

        $routeParams = [];
        if (!$evaluationReport->isEmpty()) {
            $routeParams['id'] = $evaluationReport->getId();
        }

        switch ($action) {
            case 'overview':
                $linkParams = [
                    'route' => 'community/evaluation/report/list',
                ];
                break;
            case 'new':
            case 'new-list':
                $route = 'community';
                $subject = '';
                if (null !== $reportReviewer) {
                    $route = 'community/evaluation/report/create-from-report-review';
                    $subject = sprintf(
                        '%s - %s - %s',
                        $reportReviewer->getProjectReport()->getProject()->getCall(),
                        $reportReviewer->getProjectReport()->getProject()->parseFullName(),
                        $reportReviewer->getProjectReport()->parseName()
                    );
                    $routeParams['reportReviewer'] = $reportReviewer->getId();
                } elseif (null !== $versionReviewer) {
                    $route = 'community/evaluation/report/create-from-version-review';
                    $subject = sprintf(
                        '%s - %s - %s',
                        $versionReviewer->getVersion()->getProject()->getCall(),
                        $versionReviewer->getVersion()->getProject()->parseFullName(),
                        $versionReviewer->getVersion()->getVersionType()
                    );
                    $routeParams['versionReviewer'] = $versionReviewer->getId();
                }
                $text = $shortLabel
                    ? $subject
                    : sprintf($this->translator->translate('txt-create-evaluation-report-for-%s'), $subject);
                $linkParams = [
                    'route' => $route,
                    'text'  => $text
                ];
                break;
            case 'download-offline-form':
                $route = 'community';
                if (null !== $reportReviewer) {
                    $route = 'community/evaluation/report/create-from-report-review';
                    $routeParams['reportReviewer'] = $reportReviewer->getId();
                } elseif (null !== $versionReviewer) {
                    $route = 'community/evaluation/report/create-from-version-review';
                    $routeParams['versionReviewer'] = $versionReviewer->getId();
                } elseif (null !== $evaluationReport) {
                    $route = 'community/evaluation/report/update';
                }
                $linkParams = [
                    'icon'        => 'fa-file-excel-o',
                    'route'       => $route,
                    'queryParams' => ['mode' => 'offline'],
                    'text'        => $this->translator->translate('txt-download-offline-form')
                ];
                break;
            case 'view':
                $label = $shortLabel ? EvaluationReportService::parseLabel($evaluationReport, '%3$s')
                    : EvaluationReportService::parseLabel($evaluationReport);
                $linkParams = [
                    'route' => 'community/evaluation/report/view',
                    'text'  => sprintf($this->translator->translate('txt-view-evaluation-report-for-%s'), $label)
                ];
                break;
            case 'edit':
                $label = $shortLabel ? EvaluationReportService::parseLabel($evaluationReport, '%3$s')
                    : EvaluationReportService::parseLabel($evaluationReport);
                $linkParams = [
                    'route' => 'community/evaluation/report/update',
                    'text'  => sprintf($this->translator->translate('txt-update-evaluation-report-for-%s'), $label)
                ];
                break;
            case 'finalise':
                $linkParams = [
                    'route' => 'community/evaluation/report/finalise',
                    'text'  => $this->translator->translate('txt-finalise-evaluation-report')
                ];
                break;
            case 'undo-final':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/undo-final',
                    'text'  => $this->translator->translate('txt-undo-finalisation')
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
