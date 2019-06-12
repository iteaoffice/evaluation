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

use Project\Entity\Evaluation\Report2\Criterion;
use Project\View\Helper\LinkAbstract;

/**
 * Class CriterionLink
 * @package Evaluation\View\Helper\Report
 */
final class CriterionLink extends LinkAbstract
{
    /**
     * @var Criterion
     */
    private $criterion;

    public function __invoke(
        Criterion $criterion = null,
        $action = 'view',
        $show = 'name'
    ): string {
        $this->criterion = $criterion ?? new Criterion();
        $this->setAction($action);
        $this->setShow($show);

        $this->addRouterParam('id', $this->criterion->getId());
        $this->setShowOptions(['name' => $this->criterion->getCriterion()]);

        return $this->createLink();
    }

    /**
     * @throws \Exception
     */
    public function parseAction(): void
    {
        switch ($this->getAction()) {
            case 'new':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/new');
                $this->setText($this->translator->translate("txt-new-evaluation-report-criterion"));
                break;
            case 'list':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/list');
                $this->setText($this->translator->translate("txt-list-evaluation-report-criterion-list"));
                break;
            case 'view':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/view');
                $this->setText(\sprintf(
                    $this->translator->translate("txt-view-evaluation-report-criterion-%s"),
                    $this->criterion->getCriterion()
                ));
                break;
            case 'edit':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/edit');
                $this->setText(\sprintf(
                    $this->translator->translate("txt-edit-evaluation-report-criterion-%s"),
                    $this->criterion->getCriterion()
                ));
                break;
            default:
                throw new \Exception(\sprintf("%s is an incorrect action for %s", $this->getAction(), __CLASS__));
        }
    }
}
