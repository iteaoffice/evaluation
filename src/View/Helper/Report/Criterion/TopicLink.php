<?php

/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @topic       Project
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
use Evaluation\View\Helper\AbstractLink;

/**
 * Class TopicLink
 *
 * @package Evaluation\View\Helper\Report\Criterion
 */
final class TopicLink extends AbstractLink
{
    public function __invoke(
        Topic $topic = null,
        string $action = 'view',
        string $show = 'name'
    ): string {
        $this->reset();

        $this->extractRouteParams($topic, ['id']);
        if (null !== $topic) {
            $this->addShowOption('name', $topic->getTopic());
        }

        $this->parseAction($action, $topic ?? new Topic());

        return $this->createLink($show);
    }

    public function parseAction(string $action, Topic $topic): void
    {
        $this->action = $action;

        switch ($action) {
            case 'new':
                $this->setRoute('zfcadmin/evaluation/report/criterion/topic/new');
                $this->setText($this->translator->translate('txt-new-evaluation-report-criterion-topic'));
                break;
            case 'list':
                $this->setRoute('zfcadmin/evaluation/report/criterion/topic/list');
                $this->setText($this->translator->translate('txt-evaluation-report-criterion-topic-list'));
                break;
            case 'view':
                $this->setRoute('zfcadmin/evaluation/report/criterion/topic/view');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-view-evaluation-report-criterion-topic-%s'),
                        $topic->getTopic()
                    )
                );
                break;
            case 'edit':
                $this->setRoute('zfcadmin/evaluation/report/criterion/topic/edit');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-edit-evaluation-report-criterion-topic-%s'),
                        $topic->getTopic()
                    )
                );
                break;
        }
    }
}
