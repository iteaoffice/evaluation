<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Form\Report;

use Doctrine\ORM\EntityManager;
use DoctrineORMModule\Form\Element\EntityMultiCheckbox;
use Evaluation\Entity\Report\Version;
use Evaluation\Entity\Report\Window;
use Evaluation\Form\ObjectFieldset;

final class WindowFieldset extends ObjectFieldset
{
    public function __construct(EntityManager $entityManager, Window $window)
    {
        parent::__construct($entityManager, $window);

        /** @var EntityMultiCheckbox $reportVersionsElement */
        $reportVersionsElement = $this->get('reportVersions');

        $currentOptions = $reportVersionsElement->getValueOptions();
        // Add archived current report versions
        /** @var Version $reportVersion */
        foreach ($window->getReportVersions() as $reportVersion) {
            if ($reportVersion->getArchived()) {
                $currentOptions[$reportVersion->getId()] = $reportVersion->getLabel();
            }
        }

        $reportVersionsElement->setValueOptions($currentOptions);
    }
}
