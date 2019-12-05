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

namespace Evaluation\View\Helper\Report\Criterion;

use Evaluation\Entity\Report\Criterion\Category;
use Evaluation\View\Helper\AbstractLink;

/**
 * Class CategoryLink
 *
 * @package Evaluation\View\Helper\Report\Criterion
 */
final class CategoryLink extends AbstractLink
{
    public function __invoke(
        Category $category = null,
        string $action = 'view',
        string $show = 'name'
    ): string {
        $this->extractRouteParams($category, ['id']);

        if (null !== $category) {
            $this->addShowOption('name', $category->getCategory());
        }

        $this->parseAction($action, $category ?? new Category());

        return $this->createLink($show);
    }

    public function parseAction(string $action, Category $category): void
    {
        $this->action = $action;

        switch ($action) {
            case 'new':
                $this->setRoute('zfcadmin/evaluation/report/criterion/category/new');
                $this->setText($this->translator->translate('txt-new-evaluation-report-criterion-category'));
                break;
            case 'list':
                $this->setRoute('zfcadmin/evaluation/report/criterion/category/list');
                $this->setText($this->translator->translate('txt-list-evaluation-report-criterion-categories'));
                break;
            case 'view':
                $this->setRoute('zfcadmin/evaluation/report/criterion/category/view');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-view-evaluation-report-criterion-category-%s'),
                        $category->getCategory()
                    )
                );
                break;
            case 'edit':
                $this->setRoute('zfcadmin/evaluation/report/criterion/category/edit');
                $this->setText(
                    $this->translator->translate('txt-edit-evaluation-report-criterion-category'),
                );
                break;
        }
    }
}
