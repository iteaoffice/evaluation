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

namespace Project\Form\Evaluation\Report2\Criterion;

use Doctrine\ORM\EntityManager;
use Project\Entity\Evaluation\Report2\Criterion;
use Project\Entity\Evaluation\Report2\Criterion\Version as CriterionVersion;
use Project\Form\ObjectFieldset;
use Zend\Form\Element\Select;

final class VersionFieldset extends ObjectFieldset
{
    public function __construct(EntityManager $entityManager, CriterionVersion $criterionVersion)
    {
        parent::__construct($entityManager, $criterionVersion);

        /** @var Select $criterionElement */
        $criterionElement = $this->get('criterion');
        $valueOptions     = [];

        $criteria = $entityManager->getRepository(Criterion::class)->findForVersion($criterionVersion);
        /** @var Criterion $criterion */
        foreach ($criteria as $criterion) {
            $valueOptions[$criterion->getId()] = \sprintf(
                '%d: %s',
                $criterion->getId(),
                (string) $criterion
            );
        }

        $criterionElement->setValueOptions($valueOptions);
    }
}
