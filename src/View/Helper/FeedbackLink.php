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

namespace Evaluation\View\Helper;

use Evaluation\Acl\Assertion\FeedbackAssertion;
use Evaluation\Entity\Feedback;
use General\ValueObject\Link\Link;
use General\ValueObject\Link\LinkDecoration;
use Project\Entity\Version\Version;
use function sprintf;
use function strtoupper;

/**
 * Class FeedbackLink
 *
 * @package Evaluation\View\Helper
 */
final class FeedbackLink extends \General\View\Helper\AbstractLink
{
    public function __invoke(
        Feedback $feedback = null,
        string   $action = 'view',
        string   $show = LinkDecoration::SHOW_TEXT,
        Version  $version = null
    ): string
    {
        $feedback ??= new Feedback();

        if (!$this->hasAccess($feedback, FeedbackAssertion::class, $action)) {
            return '';
        }

        $routeParams = [];
        if (!$feedback->isEmpty()) {
            $routeParams['id'] = $feedback->getId();
        }

        if (null !== $version) {
            $routeParams['version'] = $version->getId();
        }

        switch ($action) {
            case 'new':
                $linkParams = [
                    'route' => 'zfcadmin/feedback/new',
                    'text'  => $this->translator->translate('txt-add-feedback')
                ];
                break;
            case 'edit-admin':
                $linkParams = [
                    'icon'  => 'fa-pencil-square-o',
                    'route' => 'zfcadmin/feedback/edit',
                    'text'  => sprintf(
                        $this->translator->translate('txt-edit-%s-feedback-of-project-%s'),
                        strtoupper($feedback->getVersion()->getVersionType()->getType()),
                        $feedback->getVersion()->getProject()
                    )
                ];
                break;
            case 'view-admin':
                $linkParams = [
                    'route' => 'zfcadmin/feedback/view',
                    'text'  => sprintf(
                        $this->translator->translate('txt-view-%s-feedback-of-project-%s'),
                        strtoupper($feedback->getVersion()->getVersionType()->getType()),
                        $feedback->getVersion()->getProject()
                    )
                ];
                break;
            case 'edit':
                $linkParams = [
                    'route' => 'community/project/edit/feedback',
                    'text'  => sprintf(
                        $this->translator->translate('txt-edit-%s-feedback'),
                        strtoupper($feedback->getVersion()->getVersionType()->getType())
                    )
                ];
                break;
            case 'view':
                $linkParams = [
                    'route' => 'community/project/feedback',
                    'text'  => sprintf(
                        $this->translator->translate('txt-give-%s-feedback'),
                        strtoupper($feedback->getVersion()->getVersionType()->getType())
                    )
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
