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

namespace Evaluation\Navigation\Invokable\Reviewer;

use Admin\Navigation\Invokable\AbstractNavigationInvokable;
use Evaluation\Entity\Reviewer\Contact;
use Zend\Navigation\Page\Mvc;

/**
 * Class ContactLabel
 * @package Evaluation\Navigation\Invokable\Reviewer
 */
final class ContactLabel extends AbstractNavigationInvokable
{
    /**
     * Set the review contact navigation label
     *
     * @param Mvc $page
     *
     * @return void;
     */
    public function __invoke(Mvc $page): void
    {
        if ($this->getEntities()->containsKey(Contact::class)) {
            /** @var Contact $contact */
            $contact = $this->getEntities()->get(Contact::class);
            $page->setParams(array_merge($page->getParams(), ['id' => $contact->getId()]));
            $label = (string)$contact;
        } else {
            $label = $this->translator->translate('txt-nav-view');
        }
        $page->set('label', $label);
    }
}
