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

use Evaluation\Entity\Report\Criterion\Version as CriterionVersion;
use Evaluation\Entity\Report\Version as ReportVersion;
use Evaluation\View\Helper\AbstractLink;
use function sprintf;

/**
 * Class VersionLink
 *
 * @package Evaluation\View\Helper\Report\Criterion
 */
final class VersionLink extends AbstractLink
{
    public function __invoke(
        CriterionVersion $version = null,
        string $action = 'view',
        string $show = 'name',
        ReportVersion $reportVersion = null
    ): string {

        $this->extractRouteParams($version, ['id']);

        if (null !== $reportVersion) {
            $this->addRouteParam('reportVersionId', $reportVersion->getId());
        }
        if (null !== $version) {
            $this->addShowOption('name', (string)$version->getCriterion());
        }

        $this->parseAction($action, $version ?? new CriterionVersion());

        return $this->createLink($show);
    }

    public function parseAction(string $action, CriterionVersion $version): void
    {
        $this->action = $action;

        switch ($action) {
            case 'add':
                $this->setLinkIcon('fa-plus');
                $this->setRoute('zfcadmin/evaluation/report/criterion/version/add');
                $this->setText($this->translator->translate('txt-add-new-evaluation-report-criterion'));
                break;
            case 'view':
                $this->setRoute('zfcadmin/evaluation/report/criterion/version/view');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-view-evaluation-report-criterion-%s'),
                        $version->getCriterion()
                    )
                );
                break;
            case 'edit':
                $this->setRoute('zfcadmin/evaluation/report/criterion/version/edit');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-edit-evaluation-report-criterion-%s'),
                        $version->getCriterion()
                    )
                );
                break;
        }
    }
}
