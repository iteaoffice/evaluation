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

namespace Evaluation\View\Helper\Report\Criterion;

use Evaluation\Entity\Report\Criterion\Topic;
use General\View\Helper\AbstractLink;
use General\ValueObject\Link\Link;

/**
 * Class TopicLink
 * @package Evaluation\View\Helper\Report\Criterion
 */
final class TopicLink extends AbstractLink
{
    public function __invoke(
        Topic  $topic = null,
        string $action = 'view',
        string $show = 'name'
    ): string {
        $topic ??= new Topic();

        $routeParams = [];
        $showOptions = [];
        if (! $topic->isEmpty()) {
            $routeParams['id']   = $topic->getId();
            $showOptions['name'] = $topic->getTopic();
        }

        switch ($action) {
            case 'new':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/topic/new',
                    'text'  => $showOptions[$show]
                        ?? $this->translator->translate('txt-new-evaluation-report-criterion-topic')
                ];
                break;
            case 'list':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/topic/list',
                    'text'  => $showOptions[$show]
                        ?? $this->translator->translate('txt-evaluation-report-criterion-topic-list')
                ];
                break;
            case 'view':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/topic/view',
                    'text'  => $showOptions[$show] ?? sprintf(
                        $this->translator->translate('txt-view-evaluation-report-criterion-topic-%s'),
                        $topic->getTopic()
                    )
                ];
                break;
            case 'edit':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/topic/edit',
                    'text'  => $showOptions[$show] ?? sprintf(
                        $this->translator->translate('txt-edit-evaluation-report-criterion-topic-%s'),
                        $topic->getTopic()
                    )
                ];
                break;
        }
        $linkParams['action']      = $action;
        $linkParams['show']        = $show;
        $linkParams['routeParams'] = $routeParams;

        return $this->parse(Link::fromArray($linkParams));
    }
}
