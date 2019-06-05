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

namespace Evaluation\Navigation\Invokable\Report\Criterion;

use Admin\Navigation\Invokable\AbstractNavigationInvokable;
use Evaluation\Entity\Report\Criterion\Topic;
use Zend\Navigation\Page\Mvc;

/**
 * Class TopicLabel
 * @package Evaluation\Navigation\Invokable\Report\Criterion
 */
final class TopicLabel extends AbstractNavigationInvokable
{
    public function __invoke(Mvc $page): void
    {
        if ($this->getEntities()->containsKey(Topic::class)) {
            /** @var Topic $topic */
            $topic = $this->getEntities()->get(Topic::class);
            $page->setParams(\array_merge($page->getParams(), ['id' => $topic->getId()]));
            $label = (string) $topic->getTopic();
        } else {
            $label = $this->translator->translate('txt-nav-view');
        }
        $page->set('label', $label);
    }
}
