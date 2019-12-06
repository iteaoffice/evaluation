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

use Evaluation\Acl\Assertion\ReviewerAssertion;
use Evaluation\Entity\Reviewer;
use General\ValueObject\Link\Link;
use General\ValueObject\Link\LinkDecoration;
use Project\Entity\Project;

/**
 * Class ReviewerLink
 *
 * @package Evaluation\View\Helper
 */
final class ReviewerLink extends \General\View\Helper\AbstractLink
{
    public function __invoke(
        Reviewer $reviewer = null,
        string   $action = 'new',
        string   $show = LinkDecoration::SHOW_TEXT,
        Project  $project = null
    ): string
    {
        $reviewer ??= new Reviewer();

        if (!$this->hasAccess($reviewer, ReviewerAssertion::class, $action)) {
            return '';
        }

        $routeParams = [];
        if (!$reviewer->isEmpty()) {
            $routeParams['id'] = $reviewer->getId();
        }
        if ($project instanceof Project) {
            $routeParams['projectId'] = $project->getId();
        }

        switch ($action) {
            case 'list-contacts':
                $linkParams = [
                    'icon'  => 'fa-users',
                    'route' => 'zfcadmin/evaluation/reviewer/list',
                    'text'  => $this->translator->translate('txt-show-review-contacts')
                ];
                break;
            case 'new':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/reviewer/new',
                    'text'  => $this->translator->translate('txt-new-review-contact')
                ];
                break;
            case 'edit':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/reviewer/edit',
                    'text'  => $this->translator->translate('txt-edit-review-contact')
                ];
                break;
            case 'delete':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/reviewer/delete',
                    'text'  => sprintf(
                        $this->translator->translate('txt-remove-%s-from-this-project'),
                        $reviewer->getContact()->parseFullName()
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
