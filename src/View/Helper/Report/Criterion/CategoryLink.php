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

use Evaluation\Entity\Report\Criterion\Category;
use Evaluation\View\Helper\AbstractLink;

/**
 * Class CategoryLink
 * @package Evaluation\View\Helper\Report\Criterion
 */
final class CategoryLink extends AbstractLink
{
    /**
     * @var Category
     */
    private $category;

    public function __invoke(
        Category $category = null,
        string   $action = 'view',
        string   $show = 'name'
    ): string {
        $this->category = $category ?? new Category();
        $this->setAction($action);
        $this->setShow($show);

        $this->addRouterParam('id', $this->category->getId());
        $this->setShowOptions([
            'name' => $this->category->getCategory()
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
                $this->setRouter('zfcadmin/evaluation/report2/criterion/category/new');
                $this->setText($this->translator->translate("txt-new-evaluation-report-critertion-category"));
                break;
            case 'list':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/category/list');
                $this->setText($this->translator->translate("txt-list-evaluation-report-critertion-categories"));
                break;
            case 'view':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/category/view');
                $this->setText(sprintf(
                    $this->translator->translate("txt-view-evaluation-report-critertion-category-%s"),
                    $this->category->getCategory()
                ));
                break;
            case 'edit':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/category/edit');
                $this->setText(sprintf(
                    $this->translator->translate("txt-edit-evaluation-report-critertion-category-%s"),
                    $this->category->getCategory()
                ));
                break;
            default:
                throw new \Exception(sprintf("%s is an incorrect action for %s", $this->getAction(), __CLASS__));
        }
    }
}
