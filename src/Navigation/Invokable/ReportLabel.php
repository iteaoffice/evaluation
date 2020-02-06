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

namespace Evaluation\Navigation\Invokable;

use General\Navigation\Invokable\AbstractNavigationInvokable;
use General\Navigation\Service\NavigationService;
use Evaluation\Service\EvaluationReportService;
use Evaluation\Entity\Report as EvaluationReport;
use Laminas\I18n\Translator\TranslatorInterface;
use Project\Entity\Report\Report;
use Project\Entity\Version\Reviewer as VersionReviewer;
use Project\Entity\Report\Reviewer as ReportReviewer;
use Project\Entity\Version\Version;
use Laminas\Navigation\Page\Mvc;

/**
 * Class ReportLabel
 * @package Project\Navigation\Invokable\Evaluation
 */
final class ReportLabel extends AbstractNavigationInvokable
{
    private EvaluationReportService $evaluationReportService;

    public function __construct(
        NavigationService $navigationService,
        TranslatorInterface $translator,
        EvaluationReportService $evaluationReportService
    ) {
        parent::__construct($navigationService, $translator);
        $this->evaluationReportService = $evaluationReportService;
    }

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
        if ($this->getEntities()->containsKey(VersionReviewer::class)) {
            /** @var VersionReviewer $review */
            $review = $this->getEntities()->get(VersionReviewer::class);
            $this->getEntities()->set(Version::class, $review->getVersion());
            $label = $this->translator->translate('txt-nav-create-evaluation-report');
        } elseif ($this->getEntities()->containsKey(ReportReviewer::class)) {
            /** @var ReportReviewer $review */
            $review = $this->getEntities()->get(ReportReviewer::class);
            $this->getEntities()->set(Report::class, $review->getProjectReport());
            $label = $this->translator->translate('txt-nav-create-evaluation-report');
        } elseif ($this->getEntities()->containsKey(EvaluationReport::class)) {
            /** @var EvaluationReportService $evaluationReportService */
            $report = $this->getEntities()->get(EvaluationReport::class);
            $label  = $this->evaluationReportService->parseLabel($report);
        }

        $page->set('label', $label);
    }
}
