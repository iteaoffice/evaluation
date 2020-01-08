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

namespace Evaluation\Navigation\Invokable\Report\Criterion;

use Admin\Navigation\Invokable\AbstractNavigationInvokable;
use Evaluation\Entity\Report\Criterion\Type;
use Laminas\Navigation\Page\Mvc;

final class TypeLabel extends AbstractNavigationInvokable
{
    public function __invoke(Mvc $page): void
    {
        if ($this->getEntities()->containsKey(Type::class)) {
            /** @var Type $type */
            $type = $this->getEntities()->get(Type::class);
            $page->setParams(\array_merge($page->getParams(), ['id' => $type->getId()]));
            $label = (string) $type->getType();
        } else {
            $label = $this->translator->translate('txt-nav-view');
        }
        $page->set('label', $label);
    }
}
