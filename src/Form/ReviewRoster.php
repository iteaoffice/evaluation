<?php
/**
 * ITEA Office all rights reserved
 *
 * @category    Content
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2019 ITEA Office (https://itea3.org)
 */

declare(strict_types=1);

namespace Evaluation\Form;

use Evaluation\Service\ReviewerService;
use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\File\Extension;

/**
 * Class ReviewRoster
 * @package Evaluation\Form
 */
final class ReviewRoster extends Form
{
    /**
     * ReviewRoster constructor.
     */
    public function __construct()
    {
        parent::__construct('review_roster');
        $this->setAttributes([
            'method'  => 'post',
            'role'    => 'form',
            'class'   => 'form-horizontal',
            'enctype' => 'multipart/form-data'
        ]);

        // Set a basic input filter
        $inputFilter = new InputFilter();
        $inputFilter->add([
            'name'       => 'excel',
            'required'   => true,
            'validators' => [
                new Extension(['extension' => ['xlsx']]),
            ],
        ]);
        $this->setInputFilter($inputFilter);

        $this->add([
            'type'       => Element\File::class,
            'name'       => 'excel',
            'options'    => [
                'label'      => _('txt-file'),
                'help-block' => _('txt-select-review-roster-configuration-file')
            ],
            'attributes' => [
                'accept' => '.xlsx',
            ],
        ]);

        $this->add([
            'type'       => Element\Select::class,
            'name'       => 'type',
            'options'    => [
                'label'          => _('txt-type'),
                'help-block'    => _('txt-select-the-review-type'),
                'value_options' => [
                    ReviewerService::TYPE_PPR => _('txt-progress-report'),
                    ReviewerService::TYPE_PO  => _('txt-project-outline'),
                    ReviewerService::TYPE_FPP => _('txt-full-project-proposal'),
                    ReviewerService::TYPE_CR  => _('txt-change-request')
                ],
            ],
        ]);

        $this->add([
            'type'       => Element\Number::class,
            'name'       => 'nr',
            'options'    => [
                'label'      => _('txt-reviewers-per-project'),
                'help-block' => _('txt-the-minimum-number-of-reviewers-assigned-to-a-project'),
            ],
            'attributes' => [
                'min'   => 1,
                'step'  => 1,
                'value' => 3
            ]
        ]);

        $this->add([
            'type'       => Element\Checkbox::class,
            'name'       => 'include-spare',
            'options'    => [
                'label'      => _('txt-include-spare-reviewers'),
                'help-block' => _('txt-include-spare-reviewers-in-the-minimum-number-of-reviewers-assigned-to-a-project'),
            ],
        ]);

        $this->add([
            'type'        => Element\Number::class,
            'name'        => 'projects',
            'options'     => [
                'label'      => _('txt-projects-per-round'),
                'help-block' => _('txt-force-the-maximum-number-of-projects-per-review-round-(-leave-empty-for-automatic-calculation-)'),
            ],
            'attributes'  => [
                'min'   => 1,
                'step'  => 1,
            ]
        ]);
        // Disable the required input filter setting that comes default with Element\Number::class
        // Setting required in the element specification above has no effect...
        $this->getInputFilter()->get('projects')->setRequired(false);

        $this->add([
            'type'       => Element\Submit::class,
            'name'       => 'submit',
            'attributes' => [
                'class' => 'btn btn-primary',
                'value' => _('txt-generate-review-roster'),
            ],
        ]);
    }
}
