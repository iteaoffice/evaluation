<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\InputFilter\Report\Criterion;

use Doctrine\ORM\EntityManager;
use DoctrineModule\Validator\UniqueObject;
use Evaluation\Entity\Report\Criterion\Type;
use Laminas\InputFilter\InputFilter;

/**
 * Class TypeFilter
 * @package Evaluation\InputFilter\Report\Criterion
 */
final class TypeFilter extends InputFilter
{
    public function __construct(EntityManager $entityManager)
    {
        $inputFilter = new InputFilter();
        $inputFilter->add(
            [
                'name'       => 'type',
                'required'   => true,
                'filters'    => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 1,
                            'max'      => 100,
                        ],
                    ],
                    [
                        'name'    => UniqueObject::class,
                        'options' => [
                            'object_repository' => $entityManager->getRepository(Type::class),
                            'object_manager'    => $entityManager,
                            'use_context'       => true,
                            'fields'            => 'type',
                        ],
                    ],
                ],
            ]
        );

        $inputFilter->add(
            [
                'name'     => 'category',
                'required' => true,
            ]
        );
        $inputFilter->add(
            [
                'name'     => 'sequence',
                'required' => false,
            ]
        );
        $this->add($inputFilter, 'evaluation_entity_report_criterion_type');
    }
}
