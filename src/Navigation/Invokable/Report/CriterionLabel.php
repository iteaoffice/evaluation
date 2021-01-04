<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Navigation\Invokable\Report;

use General\Navigation\Invokable\AbstractNavigationInvokable;
use Evaluation\Entity\Report\Criterion;
use Laminas\Navigation\Page\Mvc;

/**
 * Class CriterionLabel
 * @package Evaluation\Navigation\Invokable\Report
 */
final class CriterionLabel extends AbstractNavigationInvokable
{
    public function __invoke(Mvc $page): void
    {
        if ($this->getEntities()->containsKey(Criterion::class)) {
            /** @var Criterion $criterion */
            $criterion = $this->getEntities()->get(Criterion::class);
            $page->setParams(\array_merge($page->getParams(), ['id' => $criterion->getId()]));
            $label = (string)$criterion->getCriterion();
        } else {
            $label = $this->translator->translate('txt-nav-view');
        }
        $page->set('label', $label);
    }
}
