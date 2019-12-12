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

use Zend\Form\Element;
use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\Validator\File\Extension;

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
