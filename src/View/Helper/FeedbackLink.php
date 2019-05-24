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

use Evaluation\Entity\Feedback;
use InvalidArgumentException;
use Project\Acl\Assertion\Evaluation\Feedback as FeedbackAssertion;
use Project\Entity\Version\Version;
use function sprintf;
use function strtoupper;

/**
 * Class FeedbackLink
 * @package Evaluation\View\Helper
 */
final class FeedbackLink extends AbstractLink
{
    /**
     * @var Feedback
     */
    private $feedback;

    /**
     * @var Version
     */
    private $version;

    public function __invoke(
        Feedback $feedback = null,
        string   $action = 'view',
        string   $show = 'text',
        Version  $version = null
    ):string {
        $this->feedback = $feedback ?? new Feedback();
        $this->setAction($action);
        $this->setShow($show);
        $this->version = $version ?? new Version();

        // Set the non-standard options needed to give an other link value
        if (!$this->hasAccess($this->feedback, FeedbackAssertion::class, $this->getAction())) {
            return '';
        }

        if (!$this->feedback->isEmpty()) {
            $this->setShowOptions([
                'project'  => $this->feedback->getVersion()->getProject(),
                'status'   => $this->feedback->getStatus()->getStatus(),
                'feedback' => $this->translator->translate("txt-feedback"),
            ]);
        }

        $this->addRouterParam('id', $this->feedback->getId());
        $this->addRouterParam('entity', 'Evaluation\Feedback');

        return $this->createLink();
    }

    /**
     * Extract the relevant parameters based on the action.
     */
    public function parseAction(): void
    {
        switch ($this->getAction()) {
            case 'new':
                $this->setRouter('zfcadmin/project/feedback/new');
                $this->addRouterParam('version', $this->version->getId());
                $this->setText(sprintf($this->translator->translate("txt-add-feedback")));
                break;
            case 'edit-admin':
                $this->setRouter('zfcadmin/project/feedback/edit');
                $this->setText(sprintf(
                    $this->translator->translate("txt-edit-%s-feedback-of-project-%s"),
                    strtoupper($this->feedback->getVersion()->getVersionType()->getType()),
                    $this->feedback->getVersion()->getProject()
                ));
                break;
            case 'view-admin':
                $this->setRouter('zfcadmin/project/feedback/view');
                $this->setText(sprintf(
                    $this->translator->translate("txt-view-%s-feedback-of-project-%s"),
                    strtoupper($this->feedback->getVersion()->getVersionType()->getType()),
                    $this->feedback->getVersion()->getProject()
                ));
                break;
            case 'edit':
                $this->setRouter('community/project/edit/feedback');
                $this->setText(sprintf(
                    $this->translator->translate("txt-edit-%s-feedback"),
                    strtoupper($this->feedback->getVersion()->getVersionType()->getType())
                ));
                break;
            case 'view':
                $this->setRouter('community/project/feedback');
                $this->setText(sprintf(
                    $this->translator->translate("txt-give-%s-feedback"),
                    strtoupper($this->feedback->getVersion()->getVersionType()->getType())
                ));
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf(
                        "%s is an incorrect action for %s",
                        $this->getAction(),
                        __CLASS__
                    )
                );
        }
    }
}
