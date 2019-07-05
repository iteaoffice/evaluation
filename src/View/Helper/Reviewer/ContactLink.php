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

namespace Evaluation\View\Helper\Reviewer;

use Evaluation\Entity\Reviewer\Contact;
use Evaluation\View\Helper\AbstractLink;
use function sprintf;

/**
 * Class ContactLink
 *
 * @package Evaluation\View\Helper\Reviewer
 */
final class ContactLink extends AbstractLink
{
    public function __invoke(
        Contact $reviewContact = null,
        string $action = 'view',
        string $show = 'handle'
    ): string {
        $this->reset();

        $this->extractRouteParams($reviewContact, ['id']);
        $this->extractLinkContentFromEntity($reviewContact, ['id', 'handle']);

        $this->parseAction($action);

        return $this->createLink($show);
    }

    public function parseAction(string $action): void
    {
        $this->action = $action;

        switch ($action) {
            case 'list':
                $this->setRoute('zfcadmin/evaluation/reviewer/contact/list');
                $this->setText($this->translator->translate('txt-review-contact-list'));
                break;
            case 'new':
                $this->setRoute('zfcadmin/evaluation/reviewer/contact/new');
                $this->setText($this->translator->translate('txt-new-review-contact'));
                break;
            case 'view':
                $this->setRoute('zfcadmin/evaluation/reviewer/contact/view');
                $this->setText($this->translator->translate('txt-view-review-contact'));
                break;
            case 'edit':
                $this->setRoute('zfcadmin/evaluation/reviewer/contact/edit');
                $this->setText(sprintf($this->translator->translate('txt-edit-review-contact')));
                break;
        }
    }
}
