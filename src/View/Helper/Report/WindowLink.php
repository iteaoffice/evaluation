<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\View\Helper\Report;

use Evaluation\Entity\Report\Window;
use General\ValueObject\Link\Link;
use General\ValueObject\Link\LinkDecoration;
use General\View\Helper\AbstractLink;

/**
 * Class WindowLink
 * @package Evaluation\View\Helper\Report
 */
final class WindowLink extends AbstractLink
{
    public function __invoke(
        Window $window = null,
        string $action = 'view',
        string $show = LinkDecoration::SHOW_TEXT
    ): string {
        $window ??= new Window();

        $routeParams = [];
        $showOptions = [];
        if (! $window->isEmpty()) {
            $routeParams['id']   = $window->getId();
            $showOptions['name'] = $window->getTitle();
        }

        switch ($action) {
            case 'new':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/window/new',
                    'text'  => $showOptions[$show]
                        ?? $this->translator->translate('txt-new-evaluation-report-window')
                ];
                break;
            case 'list':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/window/list',
                    'text'  => $showOptions[$show]
                        ?? $this->translator->translate('txt-list-evaluation-report-window-list')
                ];
                break;
            case 'view':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/window/view',
                    'text'  => $showOptions[$show] ?? sprintf(
                        $this->translator->translate('txt-view-evaluation-report-window-%s'),
                        $window->getTitle()
                    )
                ];
                break;
            case 'edit':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/window/edit',
                    'text'  => $showOptions[$show] ?? sprintf(
                        $this->translator->translate('txt-edit-evaluation-report-window-%s'),
                        $window->getTitle()
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
