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

namespace Evaluation\View\Helper\Reviewer;

use Evaluation\Entity\Reviewer\Contact;
use General\ValueObject\Link\Link;
use General\ValueObject\Link\LinkDecoration;
use General\View\Helper\AbstractLink;

/**
 * Class ContactLink
 * @package Evaluation\View\Helper\Reviewer
 */
final class ContactLink extends AbstractLink
{
    public function __invoke(
        Contact $reviewContact = null,
        string  $action = 'view',
        string  $show = LinkDecoration::SHOW_TEXT
    ): string
    {
        $reviewContact ??= new Contact();

        $routeParams = [];
        $showOptions = [];
        if (!$reviewContact->isEmpty()) {
            $routeParams['id']     = $reviewContact->getId();
            $showOptions['handle'] = $reviewContact->getHandle();
        }

        switch ($action) {
            case 'list':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/reviewer/contact/list',
                    'text'  => $showOptions[$show] ?? $this->translator->translate('txt-review-contact-list')
                ];
                break;
            case 'new':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/reviewer/contact/new',
                    'text'  => $showOptions[$show] ?? $this->translator->translate('txt-new-review-contact')
                ];
                break;
            case 'view':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/reviewer/contact/view',
                    'text'  => $showOptions[$show] ?? $this->translator->translate('txt-view-review-contact')
                ];
                break;
            case 'edit':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/reviewer/contact/edit',
                    'text'  => $showOptions[$show] ?? $this->translator->translate('txt-edit-review-contact')
                ];
                break;
            default:
                return '';
        }
        $linkParams['action']      = $action;
        $linkParams['show']        = $show;
        $linkParams['routeParams'] = $routeParams;

        return $this->parse(Link::fromArray($linkParams));
    }
}
