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

namespace Evaluation\View\Helper;

use Evaluation\Acl\Assertion\FeedbackAssertion;
use Evaluation\Entity\Feedback;
use Project\Entity\Version\Version;
use function sprintf;
use function strtoupper;

/**
 * Class FeedbackLink
 *
 * @package Evaluation\View\Helper
 */
final class FeedbackLink extends AbstractLink
{
    public function __invoke(
        Feedback $feedback = null,
        string $action = 'view',
        string $show = 'text',
        Version $version = null
    ): string {
        $this->reset();

        // Set the non-standard options needed to give an other link value
        if (!$this->hasAccess($feedback ?? new Feedback(), FeedbackAssertion::class, $action)) {
            return '';
        }

        if (null !== $feedback) {
            $this->addShowOption('project', (string)$feedback->getVersion()->getProject());
            $this->addShowOption('status', (string)$feedback->getStatus()->getStatus());
            $this->addShowOption('feedback', $this->translator->translate('txt-feedback'));
        }

        $this->extractRouterParams($feedback, ['id']);

        if (null !== $version) {
            $this->addRouteParam('version', $version->getId());
        }

        $this->parseAction($action, $feedback ?? new Feedback());

        return $this->createLink($show);
    }

    public function parseAction(string $action, Feedback $feedback): void
    {
        $this->action = $action;

        switch ($action) {
            case 'new':
                $this->setRouter('zfcadmin/project/feedback/new');
                $this->setLinkIcon('fa-plus');
                $this->setText($this->translator->translate('txt-add-feedback'));
                break;
            case 'edit-admin':
                $this->setRouter('zfcadmin/project/feedback/edit');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-edit-%s-feedback-of-project-%s'),
                        strtoupper($feedback->getVersion()->getVersionType()->getType()),
                        $feedback->getVersion()->getProject()
                    )
                );
                break;
            case 'view-admin':
                $this->setRouter('zfcadmin/project/feedback/view');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-view-%s-feedback-of-project-%s'),
                        strtoupper($feedback->getVersion()->getVersionType()->getType()),
                        $feedback->getVersion()->getProject()
                    )
                );
                break;
            case 'edit':
                $this->setRouter('community/project/edit/feedback');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-edit-%s-feedback'),
                        strtoupper($feedback->getVersion()->getVersionType()->getType())
                    )
                );
                break;
            case 'view':
                $this->setRouter('community/project/feedback');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-give-%s-feedback'),
                        strtoupper($feedback->getVersion()->getVersionType()->getType())
                    )
                );
                break;
        }
    }
}
