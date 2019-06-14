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

namespace Evaluation\View\Helper\Report\Criterion;

use Evaluation\Entity\Report\Criterion;
use Evaluation\Entity\Report\Criterion\Version as CriterionVersion;
use Evaluation\Entity\Report\Version as ReportVersion;
use Evaluation\View\Helper\AbstractLink;

/**
 * Class VersionLink
 * @package Evaluation\View\Helper\Report\Criterion
 */
final class VersionLink extends AbstractLink
{
    /**
     * @var CriterionVersion
     */
    private $criterionVersion;

    public function __invoke(
        CriterionVersion $criterionVersion = null,
        string           $action = 'view',
        string           $show = 'name',
        ReportVersion    $reportVersion = null
    ): string {
        $this->criterionVersion = $criterionVersion ?? new CriterionVersion();
        if ($this->criterionVersion->getCriterion() === null) {
            $this->criterionVersion->setCriterion(new Criterion());
        }

        $this->setAction($action);
        $this->setShow($show);

        $this->addRouterParam('id', $this->criterionVersion->getId());
        if ($reportVersion instanceof ReportVersion) {
            $this->addRouterParam('reportVersionId', $reportVersion->getId());
        }
        $this->setShowOptions(['name' => (string) $this->criterionVersion->getCriterion()]);

        return $this->createLink();
    }

    /**
     * @throws \Exception
     */
    public function parseAction(): void
    {
        switch ($this->getAction()) {
            case 'add':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/version/add');
                $this->setText($this->translator->translate("txt-add-new-evaluation-report-criterion"));
                break;
            case 'view':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/version/view');
                $this->setText(\sprintf(
                    $this->translator->translate("txt-view-evaluation-report-criterion-%s"),
                    $this->criterionVersion->getCriterion()
                ));
                break;
            case 'edit':
                $this->setRouter('zfcadmin/evaluation/report2/criterion/version/edit');
                $this->setText(\sprintf(
                    $this->translator->translate("txt-edit-evaluation-report-criterion-%s"),
                    $this->criterionVersion->getCriterion()
                ));
                break;
            default:
                throw new \Exception(\sprintf("%s is an incorrect action for %s", $this->getAction(), __CLASS__));
        }
    }
}
