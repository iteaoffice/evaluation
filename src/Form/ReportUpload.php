<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Form;

use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\File\Extension;

/**
 * Class ReportUpload
 * @package Evaluation\Form
 */
final class ReportUpload extends Form
{
    public function __construct(string $action)
    {
        parent::__construct('evaluation_report_upload');

        $this->setAttributes([
            'method' => 'post',
            'role'   => 'form',
            'class'  => 'form-horizontal',
            'action' => $action
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
                'help-block' => _('txt-select-a-filled-in-evaluation-report-excel')
            ],
            'attributes' => [
                'accept' => '.xlsx',
            ],
        ]);

        $this->add([
            'type'       => Element\Submit::class,
            'name'       => 'upload',
            'attributes' => [
                'class' => 'btn btn-primary',
                'value' => _('txt-upload-offline-form'),
            ],
        ]);
    }
}
