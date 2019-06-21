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
use InvalidArgumentException;
use function sprintf;

/**
 * Class ContactLink
 * @package Evaluation\View\Helper\Reviewer
 */
final class ContactLink extends AbstractLink
{
    /**
     * @var Contact
     */
    private $reviewContact;

    public function __invoke(
        Contact $reviewContact = null,
        string  $action = 'view',
        string  $show = 'handle'
    ): string
    {
        $this->reviewContact = $reviewContact ?? new Contact();
        $this->setAction($action);
        $this->setShow($show);
        $this->addRouterParam('id', $this->reviewContact->getId());
        $this->setShowOptions([
            'id'     => $this->reviewContact->getId(),
            'handle' => $this->reviewContact->getHandle(),
        ]);

        return $this->createLink();
    }

    public function parseAction(): void
    {
        switch ($this->getAction()) {
            case 'list':
                $this->setRouter('zfcadmin/evaluation/reviewer/contact/list');
                $this->setText($this->translator->translate("txt-review-contact-list"));
                break;
            case 'new':
                $this->setRouter('zfcadmin/evaluation/reviewer/contact/new');
                $this->setText($this->translator->translate("txt-new-review-contact"));
                break;
            case 'view':
                $this->setRouter('zfcadmin/evaluation/reviewer/contact/view');
                $this->setText($this->translator->translate("txt-view-review-contact"));
                break;
            case 'edit':
                $this->setRouter('zfcadmin/evaluation/reviewer/contact/edit');
                $this->setText(sprintf($this->translator->translate("txt-edit-review-contact")));
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf("%s is an incorrect action for %s", $this->getAction(), __CLASS__)
                );
        }
    }
}
