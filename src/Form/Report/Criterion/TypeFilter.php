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
use DoctrineORMModule\Form\Element\EntityMultiCheckbox;
use Evaluation\Entity\Report\Criterion\Category;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;

final class TypeFilter extends Form
{
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->setAttribute('method', 'get');
        $this->setAttribute('action', '');

        $filterFieldset = new Fieldset('filter');

        $filterFieldset->add(
            [
                'type'       => Element\Text::class,
                'name'       => 'search',
                'attributes' => [
                    'class'       => 'form-control',
                    'placeholder' => _('txt-search'),
                ],
            ]
        );

        $filterFieldset->add(
            [
                'type'    => EntityMultiCheckbox::class,
                'name'    => 'category',
                'options' => [
                    'target_class'   => Category::class,
                    'find_method'    => [
                        'name'   => 'findAll',
                        'params' => [
                            'criteria' => [],
                            'orderBy'  => [
                                'category' => 'ASC',
                            ],
                        ],
                    ],
                    'inline'         => true,
                    'object_manager' => $entityManager,
                    'label'          => _("txt-category"),
                ],
            ]
        );

        $this->add($filterFieldset);

        $this->add(
            [
                'type'       => Element\Submit::class,
                'name'       => 'submit',
                'attributes' => [
                    'id'    => 'submit',
                    'class' => 'btn btn-primary',
                    'value' => _('txt-filter'),
                ],
            ]
        );

        $this->add(
            [
                'type'       => Element\Submit::class,
                'name'       => 'clear',
                'attributes' => [
                    'id'    => 'cancel',
                    'class' => 'btn btn-warning',
                    'value' => _('txt-cancel'),
                ],
            ]
        );
    }
}
