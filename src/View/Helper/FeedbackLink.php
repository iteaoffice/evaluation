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
        string   $type = LinkDecoration::TYPE_TEXT,
        Version  $version = null
    ): string
    {
        $feedback ??= new Feedback();

        if (!$this->hasAccess($feedback, FeedbackAssertion::class, $action)) {
            return '';
        }

        $showOptions = [];
        $routeParams = [];
        if (!$feedback->isEmpty()) {
            $showOptions = [
                'project'  => (string) $feedback->getVersion()->getProject(),
                'status'   => (string) $feedback->getStatus()->getStatus(),
                'feedback' => $this->translator->translate('txt-feedback')
            ];
            $routeParams['id'] = $feedback->getId();
        }

        if (null !== $version) {
            $routeParams['version'] = $version->getId();
        }

        switch ($action) {
            case 'new':
                $linkParams = [
                    'route' => 'zfcadmin/feedback/new',
                    'text'  => $showOptions[$type] ?? $this->translator->translate('txt-add-feedback')
                ];
                break;
            case 'edit-admin':
                $linkParams = [
                    'icon'  => 'fa-pencil-square-o',
                    'route' => 'zfcadmin/feedback/edit',
                    'text'  => $showOptions[$type] ?? sprintf(
                        $this->translator->translate('txt-edit-%s-feedback-of-project-%s'),
                        strtoupper($feedback->getVersion()->getVersionType()->getType()),
                        $feedback->getVersion()->getProject()
                    )
                ];
                break;
            case 'view-admin':
                $linkParams = [
                    'route' => 'zfcadmin/feedback/view',
                    'text'  => $showOptions[$type] ?? sprintf(
                        $this->translator->translate('txt-view-%s-feedback-of-project-%s'),
                        strtoupper($feedback->getVersion()->getVersionType()->getType()),
                        $feedback->getVersion()->getProject()
                    )
                ];
                break;
            case 'edit':
                $linkParams = [
                    'route' => 'community/project/edit/feedback',
                    'text'  => $showOptions[$type] ?? sprintf(
                        $this->translator->translate('txt-edit-%s-feedback'),
                        strtoupper($feedback->getVersion()->getVersionType()->getType())
                    )
                ];
                break;
            case 'view':
                $linkParams = [
                    'route' => 'community/project/feedback',
                    'text'  => $showOptions[$type] ?? sprintf(
                        $this->translator->translate('txt-give-%s-feedback'),
                        strtoupper($feedback->getVersion()->getVersionType()->getType())
                    )
                ];
                break;
            default:
                return '';
        }
        $linkParams['action']      = $action;
        $linkParams['type']        = $type;
        $linkParams['routeParams'] = $routeParams;

        return $this->parse(Link::fromArray($linkParams));
    }
}
