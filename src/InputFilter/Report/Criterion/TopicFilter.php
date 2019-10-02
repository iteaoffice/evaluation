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

namespace Evaluation\InputFilter\Report\Criterion;

use Doctrine\ORM\EntityManager;
use DoctrineModule\Validator\UniqueObject;
use Evaluation\Entity\Report\Criterion\Topic;
use Zend\InputFilter\InputFilter;

/**
 * Class TopicFilter
 *
 * @package Evaluation\InputFilter\Report\Criterion
 */
final class TopicFilter extends InputFilter
{
    public function __construct(EntityManager $entityManager)
    {
        $inputFilter = new InputFilter();
        $inputFilter->add(
            [
                'name'       => 'topic',
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
                            'object_repository' => $entityManager->getRepository(Topic::class),
                            'object_manager'    => $entityManager,
                            'use_context'       => true,
                            'fields'            => ['topic'],
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
        $this->add($inputFilter, 'evaluation_entity_report_criterion_topic');
    }
}
