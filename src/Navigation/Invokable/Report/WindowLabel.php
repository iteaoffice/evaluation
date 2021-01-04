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
use Evaluation\Entity\Report\Window;
use Laminas\Navigation\Page\Mvc;

/**
 * Class WindowLabel
 * @package Evaluation\Navigation\Invokable\Report
 */
final class WindowLabel extends AbstractNavigationInvokable
{
    public function __invoke(Mvc $page): void
    {
        if ($this->getEntities()->containsKey(Window::class)) {
            /** @var Window $window */
            $window = $this->getEntities()->get(Window::class);
            $label = (string) $window->getTitle();

            $page->setParams(\array_merge($page->getParams(), ['id' => $window->getId()]));
        } else {
            $label = $this->translator->translate('txt-nav-view');
        }
        $page->set('label', $label);
    }
}
