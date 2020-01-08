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

use Evaluation\Entity\Report\Criterion;
use General\ValueObject\Link\Link;
use General\View\Helper\AbstractLink;

use function sprintf;

/**
 * Class CriterionLink
 * @package Evaluation\View\Helper\Report
 */
final class CriterionLink extends AbstractLink
{
    public function __invoke(
        Criterion $criterion = null,
        string $action = 'view',
        string $show = 'name'
    ): string {
        $criterion ??= new Criterion();

        $routeParams = [];
        $showOptions = [];
        if (! $criterion->isEmpty()) {
            $routeParams['id']   = $criterion->getId();
            $showOptions['name'] = $criterion->getCriterion();
        }

        switch ($action) {
            case 'new':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/new',
                    'text'  => $showOptions[$show]
                        ?? $this->translator->translate('txt-new-evaluation-report-criterion')
                ];
                break;
            case 'list':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/list',
                    'text'  => $showOptions[$show]
                        ?? $this->translator->translate('txt-evaluation-report-criterion-list')
                ];
                break;
            case 'view':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/view',
                    'text'  => $showOptions[$show] ?? sprintf(
                        $this->translator->translate('txt-view-evaluation-report-criterion-%s'),
                        $criterion->getCriterion()
                    )
                ];
                break;
            case 'edit':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/edit',
                    'text'  => $showOptions[$show] ?? sprintf(
                        $this->translator->translate('txt-edit-evaluation-report-criterion-%s'),
                        $criterion->getCriterion()
                    )
                ];
                break;
            default:
                return '';
        }
        $linkParams['maxLength']   = 40;
        $linkParams['action']      = $action;
        $linkParams['show']        = $show;
        $linkParams['routeParams'] = $routeParams;

        return $this->parse(Link::fromArray($linkParams));
    }
}
