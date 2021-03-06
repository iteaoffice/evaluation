<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Navigation\Invokable\Report\Criterion;

use General\Navigation\Invokable\AbstractNavigationInvokable;
use Evaluation\Entity\Report\Criterion\Category;
use Laminas\Navigation\Page\Mvc;

/**
 * Class CategoryLabel
 * @package Evaluation\Navigation\Invokable\Report\Criterion
 */
final class CategoryLabel extends AbstractNavigationInvokable
{
    public function __invoke(Mvc $page): void
    {
        if ($this->getEntities()->containsKey(Category::class)) {
            /** @var Category $category */
            $category = $this->getEntities()->get(Category::class);

            $page->setParams(array_merge($page->getParams(), ['id' => $category->getId()]));
            $label = (string)$category->getCategory();
        } else {
            $label = $this->translator->translate('txt-nav-view');
        }
        $page->set('label', $label);
    }
}
