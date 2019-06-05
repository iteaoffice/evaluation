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

namespace Evaluation\Navigation\Invokable\Report\Criterion;

use Admin\Navigation\Invokable\AbstractNavigationInvokable;
use Evaluation\Entity\Report\Criterion\Version as CriterionVersion;
use Evaluation\Entity\Report\Version as ReportVersion;
use Zend\Navigation\Page\Mvc;

/**
 * Class VersionLabel
 * @package Evaluation\Navigation\Invokable\Report\Criterion
 */
final class VersionLabel extends AbstractNavigationInvokable
{
    public function __invoke(Mvc $page): void
    {
        if ($this->getEntities()->containsKey(CriterionVersion::class)) {
            /** @var CriterionVersion $criterionVersion */
            $criterionVersion = $this->getEntities()->get(CriterionVersion::class);
            $this->getEntities()->set(ReportVersion::class, $criterionVersion->getReportVersion());
            $page->setParams(\array_merge($page->getParams(), ['id' => $criterionVersion->getId()]));
            $label = (string) $criterionVersion;
        } else {
            $label = $this->translator->translate('txt-nav-view');
        }
        $page->set('label', $label);
    }
}
