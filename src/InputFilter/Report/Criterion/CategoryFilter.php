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

namespace Evaluation\InputFilter\Report\Criterion;

use Doctrine\ORM\EntityManager;
use DoctrineModule\Validator\UniqueObject;
use Evaluation\Entity\Report\Criterion\Category;
use Zend\InputFilter\InputFilter;

/**
 * Class CategoryFilter
 *
 * @package Evaluation\InputFilter\Report\Criterion
 */
final class CategoryFilter extends InputFilter
{
    public function __construct(EntityManager $entityManager)
    {
        $inputFilter = new InputFilter();
        $inputFilter->add(
            [
                'name'       => 'category',
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
                            'object_repository' => $entityManager->getRepository(Category::class),
                            'object_manager'    => $entityManager,
                            'use_context'       => true,
                            'fields'            => 'category',
                        ],
                    ],
                ],
            ]
        );
        $inputFilter->add(
            [
                'name'     => 'sequence',
                'required' => false,
            ]
        );
        $this->add($inputFilter, 'evaluation_entity_report_criterion_category');
    }
}
