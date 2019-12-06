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

use Evaluation\Entity\Report\Criterion\Type;
use General\View\Helper\AbstractLink;
use General\ValueObject\Link\Link;

/**
 * Class TypeLink
 * @package Evaluation\View\Helper\Report\Criterion
 */
final class TypeLink extends AbstractLink
{
    public function __invoke(
        Type   $type = null,
        string $action = 'view',
        string $show = 'name'
    ): string
    {
        $type ??= new Type();

        $routeParams = [];
        $showOptions = [];
        if (!$type->isEmpty()) {
            $routeParams['id']   = $type->getId();
            $showOptions['name'] = $type->getType();
        }

        switch ($action) {
            case 'new':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/type/new',
                    'text'  => $showOptions[$show]
                        ?? $this->translator->translate('txt-new-evaluation-report-criterion-type')
                ];
                break;
            case 'list':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/type/list',
                    'text'  => $showOptions[$show]
                        ?? $this->translator->translate('txt-evaluation-report-criterion-type-list')
                ];
                break;
            case 'view':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/type/view',
                    'text'  => $showOptions[$show] ?? sprintf(
                            $this->translator->translate('txt-view-evaluation-report-criterion-type-%s'),
                            $type->getType()
                        )
                ];
                break;
            case 'edit':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/type/edit',
                    'text'  => $showOptions[$show] ?? sprintf(
                            $this->translator->translate('txt-edit-evaluation-report-criterion-type-%s'),
                            $type->getType()
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
