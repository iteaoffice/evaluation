<?php

/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @topic       Project
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2017 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\View\Helper\Report\Criterion;

use Project\Entity\Evaluation\Report2\Criterion\Topic;
use Project\View\Helper\LinkAbstract;

/**
 * Class TopicLink
 * @package Evaluation\View\Helper\Report\Criterion
 */
final class TopicLink extends LinkAbstract
{
    /**
     * @var Topic
     */
    private $topic;

    public function __invoke(
        Topic  $topic = null,
        string $action = 'view',
        string $show = 'name'
    ): string {
        $this->topic = $topic ?? new Topic();
        $this->setAction($action);
        $this->setShow($show);

        $this->addRouterParam('id', $this->topic->getId());
        $this->setShowOptions(['name' => $this->topic->getTopic()]);

        return $this->createLink();
    }

    /**
     * @throws \Exception
     */
    public function parseAction(): void
    {
        switch ($this->getAction()) {
            case 'new':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/topic/new');
                $this->setText($this->translator->translate("txt-new-evaluation-report-critertion-topic"));
                break;
            case 'list':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/topic/list');
                $this->setText($this->translator->translate("txt-evaluation-report-critertion-topic-list"));
                break;
            case 'view':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/topic/view');
                $this->setText(sprintf(
                    $this->translator->translate("txt-view-evaluation-report-critertion-topic-%s"),
                    $this->topic->getTopic()
                ));
                break;
            case 'edit':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/topic/edit');
                $this->setText(sprintf(
                    $this->translator->translate("txt-edit-evaluation-report-critertion-topic-%s"),
                    $this->topic->getTopic()
                ));
                break;
            default:
                throw new \Exception(sprintf("%s is an incorrect action for %s", $this->getAction(), __CLASS__));
        }
    }
}
