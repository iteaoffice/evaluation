<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Form\Report\Criterion;

use Doctrine\ORM\EntityManager;
use Evaluation\Entity\Report\Criterion;
use Evaluation\Entity\Report\Criterion\Version as CriterionVersion;
use Evaluation\Form\ObjectFieldset;
use Laminas\Form\Element\Select;

use function sprintf;

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
            $valueOptions[$criterion->getId()] = sprintf(
                '%d: %s',
                $criterion->getId(),
                (string) $criterion
            );
        }

        $criterionElement->setValueOptions($valueOptions);
    }
}
