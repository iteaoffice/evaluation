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

use Evaluation\Entity\Report\Criterion;
use Evaluation\View\Helper\AbstractLink;
use function sprintf;

/**
 * Class CriterionLink
 *
 * @package Evaluation\View\Helper\Report
 */
final class CriterionLink extends AbstractLink
{
    public function __invoke(
        Criterion $criterion = null,
        string $action = 'view',
        string $show = 'name'
    ): string {
        $this->reset();

        $this->extractRouterParams($criterion, ['id']);
        if (null !== $criterion) {
            $this->addShowOption('name', $criterion->getCriterion());
        }

        $this->parseAction($action, $criterion ?? new Criterion());

        return $this->createLink($show);
    }

    public function parseAction(string $action, Criterion $criterion): void
    {
        $this->action = $action;

        switch ($action) {
            case 'new':
                $this->setRouter('zfcadmin/evaluation/report/criterion/new');
                $this->setText($this->translator->translate('txt-new-evaluation-report-criterion'));
                break;
            case 'list':
                $this->setRouter('zfcadmin/evaluation/report/criterion/list');
                $this->setText($this->translator->translate('txt-list-evaluation-report-criterion-list'));
                break;
            case 'view':
                $this->setRouter('zfcadmin/evaluation/report/criterion/view');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-view-evaluation-report-criterion-%s'),
                        $criterion->getCriterion()
                    )
                );
                break;
            case 'edit':
                $this->setRouter('zfcadmin/evaluation/report/criterion/edit');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-edit-evaluation-report-criterion-%s'),
                        $criterion->getCriterion()
                    )
                );
                break;
        }
    }
}
