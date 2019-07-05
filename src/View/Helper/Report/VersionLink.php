<?php

/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @category    Project
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2017 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\View\Helper\Report;

use Evaluation\Entity\Report\Version;
use Evaluation\View\Helper\AbstractLink;
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
        string $action = 'view',
        string $show = 'name'
    ): string {
        $this->reset();

        $this->extractRouteParams($reportVersion, ['id']);

        if (null !== $reportVersion) {
            $this->addShowOption('name', $reportVersion->getLabel());
        }

        $this->parseAction($action, $reportVersion ?? new Version());

        return $this->createLink($show);
    }

    public function parseAction(string $action, Version $version): void
    {
        $this->action = $action;

        switch ($action) {
            case 'new':
                $this->setRoute('zfcadmin/evaluation/report/version/new');
                $this->setText($this->translator->translate('txt-new-evaluation-report-version'));
                break;
            case 'list':
                $this->setRoute('zfcadmin/evaluation/report/version/list');
                $this->setText($this->translator->translate('txt-evaluation-report-version-list'));
                break;
            case 'view':
                $this->setRoute('zfcadmin/evaluation/report/version/view');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-view-%s'),
                        $version->getLabel()
                    )
                );
                break;
            case 'edit':
                $this->setRoute('zfcadmin/evaluation/report/version/edit');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-edit-%s'),
                        $version->getLabel()
                    )
                );
                break;
            case 'copy':
                $this->setLinkIcon('fa-copy');
                $this->setRoute('zfcadmin/evaluation/report/version/copy');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-copy-%s'),
                        $version->getLabel()
                    )
                );
                break;
        }
    }
}
