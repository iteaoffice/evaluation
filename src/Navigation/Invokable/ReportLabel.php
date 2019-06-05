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

namespace Evaluation\Navigation\Invokable;

use Admin\Navigation\Invokable\AbstractNavigationInvokable;
use Evaluation\Service\EvaluationReportService;
use Evaluation\Entity\Report as EvaluationReport;
use Project\Entity\Report\Report;
use Project\Entity\Version\Review as VersionReview;
use Project\Entity\Report\Review as ReportReview;
use Project\Entity\Version\Version;
use Zend\Navigation\Page\Mvc;

/**
 * Class ReportLabel
 * @package Project\Navigation\Invokable\Evaluation
 */
final class ReportLabel extends AbstractNavigationInvokable
{
    /**
     * Set the Project version evaluation report label
     *
     * @param Mvc $page
     *
     * @return void;
     */
    public function __invoke(Mvc $page): void
    {
        $label = $this->translator->translate('txt-nav-view');
        if ($this->getEntities()->containsKey(VersionReview::class)) {
            /** @var VersionReview $review */
            $review = $this->getEntities()->get(VersionReview::class);
            $this->getEntities()->set(Version::class, $review->getVersion());
            $label = $this->translator->translate('txt-nav-create-evaluation-report');
        } elseif ($this->getEntities()->containsKey(ReportReview::class)) {
            /** @var ReportReview $review */
            $review = $this->getEntities()->get(ReportReview::class);
            $this->getEntities()->set(Report::class, $review->getProjectReport());
            $label = $this->translator->translate('txt-nav-create-evaluation-report');
        } elseif ($this->getEntities()->containsKey(EvaluationReport::class)) {
            /** @var EvaluationReportService $evaluationReportService */
            $report                  = $this->getEntities()->get(EvaluationReport::class);
            $evaluationReportService = $this->getServiceLocator()->get(EvaluationReportService::class);
            $label                   = $evaluationReportService->parseLabel($report);
        }

        $page->set('label', $label);
    }
}
