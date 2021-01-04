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
use Evaluation\Entity\Report\Type as ReportType;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;

/**
 * Class VersionFilter
 * @package Evaluation\Form\Report
 */
final class VersionFilter extends Form
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
                'name'    => 'type',
                'options' => [
                    'target_class'   => ReportType::class,
                    'find_method'    => [
                        'name'   => 'findAll',
                        'params' => [
                            'criteria' => [],
                            'orderBy'  => [
                                'sequence' => 'ASC',
                            ],
                        ],
                    ],
                    'inline'         => true,
                    'object_manager' => $entityManager,
                    'label'          => _("txt-type"),
                ],
            ]
        );

        $filterFieldset->add(
            [
                'type'       => Element\Select::class,
                'name'       => 'show',
                'options'    => [
                    'value_options' => [
                        'enabled'  => _("txt-not-archived"),
                        'archived' => _("txt-archived"),
                        'all'      => _("txt-all")
                    ],
                    'inline'        => true,
                    'label'         => _('txt-show'),
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
