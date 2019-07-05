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
        string   $action = 'view',
        string   $show = 'text',
        Version  $version = null
    ): string
    {
        $this->reset();

        if ($feedback === null) {
            $feedback = new Feedback();
        }

        // Set the non-standard options needed to give an other link value
        if (!$this->hasAccess($feedback, FeedbackAssertion::class, $action)) {
            return '';
        }

        if (!$feedback->isEmpty()) {
            $this->addShowOption('project', (string)$feedback->getVersion()->getProject());
            $this->addShowOption('status', (string)$feedback->getStatus()->getStatus());
            $this->addShowOption('feedback', $this->translator->translate('txt-feedback'));
        }

        $this->extractRouteParams($feedback, ['id']);

        if (null !== $version) {
            $this->addRouteParam('version', $version->getId());
        }

        $this->parseAction($action, $feedback);

        return $this->createLink($show);
    }

    public function parseAction(string $action, Feedback $feedback): void
    {
        $this->action = $action;

        switch ($action) {
            case 'new':
                $this->setRoute('zfcadmin/feedback/new');
                $this->setLinkIcon('fa-plus');
                $this->setText($this->translator->translate('txt-add-feedback'));
                break;
            case 'edit-admin':
                $this->setRoute('zfcadmin/feedback/edit');
                $this->setText(sprintf(
                    $this->translator->translate('txt-edit-%s-feedback-of-project-%s'),
                    strtoupper($feedback->getVersion()->getVersionType()->getType()),
                    $feedback->getVersion()->getProject()
                ));
                break;
            case 'view-admin':
                $this->setRoute('zfcadmin/feedback/view');
                $this->setText(sprintf(
                    $this->translator->translate('txt-view-%s-feedback-of-project-%s'),
                    strtoupper($feedback->getVersion()->getVersionType()->getType()),
                    $feedback->getVersion()->getProject()
                ));
                break;
            case 'edit':
                $this->setRoute('community/project/edit/feedback');
                $this->setText(sprintf(
                    $this->translator->translate('txt-edit-%s-feedback'),
                    strtoupper($feedback->getVersion()->getVersionType()->getType())
                ));
                break;
            case 'view':
                $this->setRoute('community/project/feedback');
                $this->setText(sprintf(
                    $this->translator->translate('txt-give-%s-feedback'),
                    strtoupper($feedback->getVersion()->getVersionType()->getType())
                ));
                break;
        }
    }
}
