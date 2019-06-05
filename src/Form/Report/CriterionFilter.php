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
use Evaluation\Entity\Report\Type as ReportType;
use Zend\Form\Element;
use Zend\Form\Fieldset;
use Zend\Form\Form;

/**
 * Class CriterionFilter
 * @package Evaluation\Form\Report
 */
final class CriterionFilter extends Form
{
    /**
     * CirterionFilter constructor.
     *
     * @param EntityManager $entityManager
     */
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
                        'not-archived' => _("txt-not-archived"),
                        'archived'     => _("txt-archived"),
                        'all'          => _("txt-all")
                    ],
                    'inline'        => true,
                    'label'         => _('txt-show'),
                ],
            ]
        );

        $filterFieldset->add(
            [
                'type'       => Element\Select::class,
                'name'       => 'has-score',
                'options'    => [
                    'value_options' => [
                        'all' => _("txt-all"),
                        'yes' => _("txt-yes"),
                        'no'  => _("txt-no")

                    ],
                    'inline'        => true,
                    'label'         => _('txt-has-score'),
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
