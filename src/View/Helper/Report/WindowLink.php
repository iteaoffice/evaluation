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

namespace Evaluation\View\Helper\Report;

use Project\Entity\Evaluation\Report2\Window;
use Project\View\Helper\LinkAbstract;

/**
 * Class WindowLink
 * @package Project\View\Helper\Evaluation
 */
final class WindowLink extends LinkAbstract
{
    /**
     * @var Window
     */
    private $window;

    public function __invoke(
        Window $window = null,
        string $action = 'view',
        string $show = 'name'
    ): string
    {
        $this->window = $window ?? new Window();
        $this->setAction($action);
        $this->setShow($show);

        if ($this->window instanceof Window) {
            $this->addRouterParam('id', $this->window->getId());
            $this->setShowOptions([
                'name' => $this->window->getTitle()
            ]);
        }

        return $this->createLink();
    }

    /**
     * @throws \Exception
     */
    public function parseAction(): void
    {
        switch ($this->getAction()) {
            case 'new':
                $this->setRouter('zfcadmin/evaluation/report2/window/new');
                $this->setText($this->translator->translate("txt-new-evaluation-report-window"));
                break;
            case 'list':
                $this->setRouter('zfcadmin/evaluation/report2/window/list');
                $this->setText($this->translator->translate("txt-list-evaluation-report-window-list"));
                break;
            case 'view':
                $this->setRouter('zfcadmin/evaluation/report2/window/view');
                $this->setText(sprintf(
                    $this->translator->translate("txt-view-evaluation-report-window-%s"),
                    $this->window->getTitle()
                ));
                break;
            case 'edit':
                $this->setRouter('zfcadmin/evaluation/report2/window/edit');
                $this->setText(sprintf(
                    $this->translator->translate("txt-edit-evaluation-report-window-%s"),
                    $this->window->getTitle()
                ));
                break;
            default:
                throw new \Exception(sprintf("%s is an incorrect action for %s", $this->getAction(), __CLASS__));
        }
    }
}
