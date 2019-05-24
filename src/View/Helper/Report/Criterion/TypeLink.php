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

namespace Evaluation\View\Helper\Report\Criterion;

use Project\Entity\Evaluation\Report2\Criterion\Type;
use Project\View\Helper\LinkAbstract;

/**
 * Class TypeLink
 * @package Evaluation\View\Helper\Report\Criterion
 */
final class TypeLink extends LinkAbstract
{
    public function __invoke(
        Type   $type = null,
        string $action = 'view',
        string $show = 'name'
    ): string
    {
        $this->reportCriterionType = $type ?? new Type();
        $this->setAction($action);
        $this->setShow($show);

        $this->addRouterParam('id', $this->reportCriterionType->getId());
        $this->setShowOptions([
            'name' => $this->reportCriterionType->getType()
        ]);

        return $this->createLink();
    }

    /**
     * @throws \Exception
     */
    public function parseAction(): void
    {
        switch ($this->getAction()) {
            case 'new':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/type/new');
                $this->setText($this->translator->translate("txt-new-evaluation-report-critertion-type"));
                break;
            case 'list':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/type/list');
                $this->setText($this->translator->translate("txt-list-evaluation-report-critertion-type-list"));
                break;
            case 'view':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/type/view');
                $this->setText(sprintf(
                    $this->translator->translate("txt-view-evaluation-report-critertion-type-%s"),
                    $this->reportCriterionType->getType()
                ));
                break;
            case 'edit':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/type/edit');
                $this->setText(sprintf(
                    $this->translator->translate("txt-edit-evaluation-report-critertion-type-%s"),
                    $this->reportCriterionType->getType()
                ));
                break;
            default:
                throw new \Exception(sprintf("%s is an incorrect action for %s", $this->getAction(), __CLASS__));
        }
    }
}
