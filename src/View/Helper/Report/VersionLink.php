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

namespace Evaluation\View\Helper\Report;

use Evaluation\Entity\Report\Version;
use General\ValueObject\Link\Link;
use General\View\Helper\AbstractLink;
use function sprintf;

/**
 * Class VersionLink
 *
 * @package Evaluation\View\Helper\Report
 */
final class VersionLink extends AbstractLink
{
    public function __invoke(
        Version $reportVersion = null,
        string  $action = 'view',
        string  $show = 'name'
    ): string {
        $reportVersion ??= new Version();

        $routeParams = [];
        $showOptions = [];
        if (! $reportVersion->isEmpty()) {
            $routeParams['id']   = $reportVersion->getId();
            $showOptions['name'] = $reportVersion->getLabel();
        }

        switch ($action) {
            case 'new':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/version/new',
                    'text'  => $showOptions[$show]
                        ?? $this->translator->translate('txt-new-evaluation-report-version')
                ];
                break;
            case 'list':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/version/list',
                    'text'  => $showOptions[$show]
                        ?? $this->translator->translate('txt-evaluation-report-version-list')
                ];
                break;
            case 'view':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/version/view',
                    'text'  => $showOptions[$show]
                        ?? sprintf($this->translator->translate('txt-view-%s'), $reportVersion->getLabel())
                ];
                break;
            case 'edit':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/version/edit',
                    'text'  => $showOptions[$show]
                        ?? sprintf($this->translator->translate('txt-edit-%s'), $reportVersion->getLabel())
                ];
                break;
            case 'copy':
                $linkParams = [
                    'icon'  => 'fa-copy',
                    'route' => 'zfcadmin/evaluation/report/version/copy',
                    'text'  => $showOptions[$show]
                        ?? sprintf($this->translator->translate('txt-copy-%s'), $reportVersion->getLabel())
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
