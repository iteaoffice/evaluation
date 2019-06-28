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

use Evaluation\Acl\Assertion\ReviewerAssertion;
use Evaluation\Entity\Reviewer;
use Project\Entity\Project;

/**
 * Class ReviewerLink
 *
 * @package Evaluation\View\Helper
 */
final class ReviewerLink extends AbstractLink
{
    public function __invoke(
        Reviewer $reviewer = null,
        string $action = 'new',
        string $show = 'text',
        Project $project = null
    ): string {
        $this->reset();

        if (!$this->hasAccess($reviewer ?? new Reviewer(), ReviewerAssertion::class, $action)) {
            return '';
        }

        $this->extractRouterParams($reviewer, ['id']);

        if (null !== $project) {
            $this->addRouteParam('projectId', $project->getId());
        }

        $this->parseAction($action, $reviewer);

        return $this->createLink($show);
    }

    public function parseAction(string $action, ?Reviewer $reviewer): void
    {
        $this->action = $action;

        switch ($action) {
            case 'list-contacts':
                $this->setLinkIcon('fa fa-users');
                $this->setRouter('zfcadmin/evaluation/reviewer/list');
                $this->setText($this->translator->translate('txt-show-review-contacts'));
                break;
            case 'new':
                $this->setRouter('zfcadmin/evaluation/reviewer/new');
                $this->setText($this->translator->translate('txt-new-review-contact'));
                break;
            case 'edit':
                $this->setRouter('zfcadmin/evaluation/reviewer/edit');
                $this->setText($this->translator->translate('txt-edit-review-contact'));
                break;
            case 'delete':
                $this->setRouter('zfcadmin/evaluation/reviewer/delete');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-remove-%s-from-this-project'),
                        null === $reviewer ? '' : $reviewer->getContact()->parseFullName()
                    )
                );
                break;
            case 'export':
                $this->setRouter('zfcadmin/evaluation/reviewer/export');
                $this->setLinkIcon('fa-file-text-o');
                $this->setText($this->translator->translate('txt-export-review-roster-text-file'));
                break;
        }
    }
}