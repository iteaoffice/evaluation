<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\View\Helper\Report\Criterion;

use Evaluation\Entity\Report\Criterion\Category;
use General\ValueObject\Link\Link;
use General\View\Helper\AbstractLink;

/**
 * Class CategoryLink
 * @package Evaluation\View\Helper\Report\Criterion
 */
final class CategoryLink extends AbstractLink
{
    public function __invoke(
        Category $category = null,
        string $action = 'view',
        string $show = 'name'
    ): string {
        $category ??= new Category();

        $routeParams = [];
        $showOptions = [];
        if (! $category->isEmpty()) {
            $routeParams['id'] = $category->getId();
            $showOptions['name'] = $category->getCategory();
        }

        switch ($action) {
            case 'new':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/category/new',
                    'text' => $showOptions[$show]
                        ?? $this->translator->translate('txt-new-evaluation-report-criterion-category')
                ];
                break;
            case 'list':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/category/list',
                    'text' => $showOptions[$show]
                        ?? $this->translator->translate('txt-evaluation-report-criterion-category-list')
                ];
                break;
            case 'view':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/category/view',
                    'text' => $showOptions[$show] ?? sprintf(
                        $this->translator->translate('txt-view-evaluation-report-criterion-category-%s'),
                        $category->getCategory()
                    )
                ];
                break;
            case 'edit':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/category/edit',
                    'text' => $showOptions[$show] ?? sprintf(
                        $this->translator->translate('txt-edit-evaluation-report-criterion-category-%s'),
                        $category->getCategory()
                    )
                ];
                break;
        }
        $linkParams['action'] = $action;
        $linkParams['show'] = $show;
        $linkParams['routeParams'] = $routeParams;

        return $this->parse(Link::fromArray($linkParams));
    }
}
