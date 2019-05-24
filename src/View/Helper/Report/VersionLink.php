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

use Project\Entity\Evaluation\Report2\Version;
use Project\View\Helper\LinkAbstract;

/**
 * Class VersionLink
 * @package Evaluation\View\Helper\Report
 */
final class VersionLink extends LinkAbstract
{
    /**
     * @var Version
     */
    private $reportVersion;

    public function __invoke(
        Version $reportVersion = null,
        string  $action = 'view',
        string  $show = 'name'
    ): string {
        $this->reportVersion = $reportVersion ?? new Version();
        $this->setAction($action);
        $this->setShow($show);
        $this->addRouterParam('id', $this->reportVersion->getId());
        $this->setShowOptions([
            'name' => $this->reportVersion->getLabel()
        ]);

        return $this->createLink();
    }

    /**
     * @throws \Exception
     */
    public function parseAction(): void
    {
        switch ($this->getAction()) {
            case 'new':
                $this->setRouter('zfcadmin/evaluation/report2/version/new');
                $this->setText($this->translator->translate("txt-new-evaluation-report-version"));
                break;
            case 'list':
                $this->setRouter('zfcadmin/evaluation/report2/version/list');
                $this->setText($this->translator->translate("txt-evaluation-report-version-list"));
                break;
            case 'view':
                $this->setRouter('zfcadmin/evaluation/report2/version/view');
                $this->setText(\sprintf(
                    $this->translator->translate("txt-view-%s"),
                    $this->reportVersion->getLabel()
                ));
                break;
            case 'edit':
                $this->setRouter('zfcadmin/evaluation/report2/version/edit');
                $this->setText(\sprintf(
                    $this->translator->translate("txt-edit-%s"),
                    $this->reportVersion->getLabel()
                ));
                break;
            case 'copy':
                $this->setRouter('zfcadmin/evaluation/report2/version/copy');
                $this->setText(\sprintf(
                    $this->translator->translate("txt-copy-%s"),
                    $this->reportVersion->getLabel()
                ));
                break;
            default:
                throw new \Exception(sprintf("%s is an incorrect action for %s", $this->getAction(), __CLASS__));
        }
    }
}
