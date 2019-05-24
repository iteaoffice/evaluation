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

namespace Evaluation\Form\Report;

use Doctrine\ORM\EntityManager;
use DoctrineORMModule\Form\Element\EntityMultiCheckbox;
use Evaluation\Entity\Report\Version;
use Evaluation\Entity\Report\Window;
use Evaluation\Form\ObjectFieldset;
use Zend\InputFilter\InputFilterProviderInterface;

final class WindowFieldset extends ObjectFieldset implements InputFilterProviderInterface
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

    public function getInputFilterSpecification(): array
    {
        // These fields are optional
        return [
            'dateEndReport' => [
                'required' => false,
            ],
            'dateEndSelection' => [
                'required' => false,
            ]
        ];
    }
}
