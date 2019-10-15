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

namespace Evaluation\Navigation\Invokable\Reviewer;

use Admin\Navigation\Invokable\AbstractNavigationInvokable;
use Evaluation\Entity\Reviewer\Contact;
use Zend\Navigation\Page\Mvc;

/**
 * Class ContactLabel
 *
 * @package Evaluation\Navigation\Invokable\Reviewer
 */
final class ContactLabel extends AbstractNavigationInvokable
{
    public function __invoke(Mvc $page): void
    {
        $label = $this->translator->translate('txt-nav-view');

        if ($this->getEntities()->containsKey(Contact::class)) {
            /** @var Contact $contact */
            $contact = $this->getEntities()->get(Contact::class);
            $page->setParams(array_merge($page->getParams(), ['id' => $contact->getId()]));
            $label = (string)$contact;
        }
        $page->set('label', $label);
    }
}
