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

use Evaluation\Entity\Report\Criterion;
use Evaluation\Entity\Report\Criterion\Version as CriterionVersion;
use Evaluation\Entity\Report\Version as ReportVersion;
use General\ValueObject\Link\Link;
use General\View\Helper\AbstractLink;
use function sprintf;

/**
 * Class VersionLink
 * @package Evaluation\View\Helper\Report\Criterion
 */
final class VersionLink extends AbstractLink
{
    public function __invoke(
        CriterionVersion $criterionVersion = null,
        string           $action = 'view',
        string           $show = 'name',
        ReportVersion    $reportVersion = null
    ): string {
        $criterionVersion ??= (new CriterionVersion())->setCriterion(new Criterion());

        $routeParams = [];
        $showOptions = [];
        if (!$criterionVersion->isEmpty()) {
            $routeParams['id']   = $criterionVersion->getId();
            $showOptions['name'] = (string) $criterionVersion->getCriterion();
        }

        if (null !== $reportVersion) {
            $routeParams['reportVersionId'] = $reportVersion->getId();
        }

        switch ($action) {
            case 'add':
                $linkParams = [
                    'icon'  => 'fas fa-plus',
                    'route' => 'zfcadmin/evaluation/report/criterion/version/add',
                    'text'  => $this->translator->translate('txt-add-new-evaluation-report-criterion')
                ];
                break;
            case 'view':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/version/view',
                    'text'  => $showOptions[$show] ?? sprintf(
                        $this->translator->translate('txt-view-evaluation-report-criterion-%s'),
                        $criterionVersion->getCriterion()
                    )
                ];
                break;
            case 'edit':
                $linkParams = [
                    'route' => 'zfcadmin/evaluation/report/criterion/version/edit',
                    'text'  => $showOptions[$show] ?? sprintf(
                        $this->translator->translate('txt-edit-evaluation-report-criterion-%s'),
                        $criterionVersion->getCriterion()
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
