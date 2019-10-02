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

namespace Evaluation\View\Helper\Report;

use Evaluation\Entity\Report\Window;
use Evaluation\View\Helper\AbstractLink;

/**
 * Class WindowLink
 *
 * @package Evaluation\View\Helper\Report
 */
final class WindowLink extends AbstractLink
{
    public function __invoke(
        Window $window = null,
        string $action = 'view',
        string $show = 'name'
    ): string {
        $this->reset();

        $this->extractRouteParams($window, ['id']);

        if (null !== $window) {
            $this->addShowOption('name', $window->getTitle());
        }

        $this->parseAction($action, $window ?? new Window());

        return $this->createLink($show);
    }

    public function parseAction(string $action, Window $window): void
    {
        $this->action = $action;

        switch ($action) {
            case 'new':
                $this->setRoute('zfcadmin/evaluation/report/window/new');
                $this->setText($this->translator->translate('txt-new-evaluation-report-window'));
                break;
            case 'list':
                $this->setRoute('zfcadmin/evaluation/report/window/list');
                $this->setText($this->translator->translate('txt-list-evaluation-report-window-list'));
                break;
            case 'view':
                $this->setRoute('zfcadmin/evaluation/report/window/view');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-view-evaluation-report-window-%s'),
                        $window->getTitle()
                    )
                );
                break;
            case 'edit':
                $this->setRoute('zfcadmin/evaluation/report/window/edit');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-edit-evaluation-report-window-%s'),
                        $window->getTitle()
                    )
                );
                break;
        }
    }
}
