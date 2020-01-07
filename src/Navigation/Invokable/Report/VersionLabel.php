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

namespace Evaluation\Navigation\Invokable\Report;

use Admin\Navigation\Invokable\AbstractNavigationInvokable;
use Evaluation\Entity\Report\Version;
use Laminas\Navigation\Page\Mvc;

/**
 * Class VersionLabel
 * @package Evaluation\Navigation\Invokable\Report
 */
final class VersionLabel extends AbstractNavigationInvokable
{
    public function __invoke(Mvc $page): void
    {
        if ($this->getEntities()->containsKey(Version::class)) {
            /** @var Version $reportVersion */
            $reportVersion = $this->getEntities()->get(Version::class);

            $page->setParams(\array_merge($page->getParams(), ['id' => $reportVersion->getId()]));
            $label = (string) $reportVersion->getLabel();
        } else {
            $label = $this->translator->translate('txt-nav-view');
        }
        $page->set('label', $label);
    }
}
