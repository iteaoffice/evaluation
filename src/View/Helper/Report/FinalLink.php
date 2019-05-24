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

use Project\Acl\Assertion\Evaluation\Report as EvaluationReportAssertion;
use Project\Entity\Evaluation\Report2 as EvaluationReport;
use Project\Entity\Report\Report as ProjectReport;
use Project\Entity\Version\Version as ProjectVersion;
use Project\View\Helper\LinkAbstract;

/**
 * Class FinalLink
 * @package Evaluation\View\Helper\Report
 */
final class FinalLink extends LinkAbstract
{
    /**
     * @var EvaluationReport
     */
    protected $evaluationReport;

    /**
     * @var ProjectReport
     */
    private $projectReport;

    /**
     * @var ProjectVersion
     */
    private $projectVersion;

    /**
     * @param EvaluationReport|null $evaluationReport
     * @param string $action
     * @param string $show
     * @param ProjectReport|null $projectReport
     * @param ProjectVersion|null $projectVersion
     * @return string
     * @throws \Exception
     */
    public function __invoke(
        EvaluationReport $evaluationReport = null,
        string           $action = 'view',
        string           $show = 'text',
        ProjectReport    $projectReport = null,
        ProjectVersion   $projectVersion = null
    ): string {
        $this->evaluationReport = $evaluationReport ?? new EvaluationReport();
        $this->setAction($action);
        $this->setShow($show);
        $this->projectReport = $projectReport ?? new ProjectReport();
        $this->projectVersion = $projectVersion ?? new ProjectVersion();

        if (!$this->evaluationReport->isEmpty()) {
            $this->addRouterParam('id', $this->getEvaluationReport()->getId());
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
            case 'download-offline-form':
                $route = 'zfcadmin/evaluation/report2/list';
                if (!$this->projectReport->isEmpty()) {
                    $route = 'zfcadmin/evaluation/report2/create-from-report';
                    $this->setText($this->translator->translate('txt-download-offline-form'));
                    $this->addRouterParam('report', $this->projectReport->getId());
                } elseif (!$this->projectVersion->isEmpty()) {
                    $route = 'zfcadmin/evaluation/report2/create-from-version';
                    $this->setText($this->translator->translate('txt-download-offline-form'));
                    $this->addRouterParam('version', $this->projectVersion->getId());
                } elseif (!$this->getEvaluationReport()->isEmpty()) {
                    $this->setText($this->translator->translate('txt-download'));
                    $route = 'zfcadmin/evaluation/report2/update';
                }
                $this->setRouter($route);
                $this->setQuery(['mode' => 'offline']);
                break;
            case 'finalise':
                $this->setRouter('zfcadmin/evaluation/report2/finalise');
                $this->setText($this->translator->translate('txt-finalise-evaluation-report'));
                break;
            case 'undo-final':
                $this->setRouter('zfcadmin/evaluation/report2/undo-final');
                $this->setText($this->translator->translate('txt-undo-finalisation'));
                break;
            case 'download':
                $this->setRouter('zfcadmin/evaluation/report2/download');
                $this->setText($this->translator->translate('txt-download-original-version'));
                break;
            case 'download-distributable':
                $this->setRouter('zfcadmin/evaluation/report2/download');
                $this->setQuery(['format' => 'distributable']);
                $this->setText($this->translator->translate('txt-download-distributable-version'));
                break;
            case 'download-pdf':
                $this->setRouter('zfcadmin/evaluation/report2/download');
                $this->setQuery(['format' => 'pdf']);
                $this->setText($this->translator->translate('txt-download-as-pdf'));
                break;
            case 'download-distributable-pdf':
                $this->setRouter('zfcadmin/evaluation/report2/download');
                $this->setQuery(['format' => 'distributable-pdf']);
                $this->setText($this->translator->translate('txt-download-distributable-version-as-pdf'));
                break;

            default:
                throw new \Exception(sprintf("%s is an incorrect action for %s", $this->getAction(), __CLASS__));
        }
    }
}
