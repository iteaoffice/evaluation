<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\InputFilter\Report;

use Laminas\InputFilter\InputFilter;

/**
 * Class WindowFilter
 * @package Evaluation\InputFilter\Report
 */
final class WindowFilter extends InputFilter
{
    public function __construct()
    {
        $inputFilter = new InputFilter();
        $inputFilter->add([
            'name'     => 'reportVersions',
            'required' => false,
        ]);
        $inputFilter->add([
            'name'     => 'title',
            'required' => true,
            'filters'  => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
        ]);
        $inputFilter->add([
            'name'     => 'description',
            'required' => false,
            'filters'  => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
        ]);
        $inputFilter->add([
            'name'     => 'dateStartReport',
            'required' => true,
        ]);
        $inputFilter->add([
            'name'     => 'dateEndReport',
            'required' => false,
        ]);
        $inputFilter->add([
            'name'     => 'dateStartSelection',
            'required' => true,
        ]);
        $inputFilter->add([
            'name'     => 'dateEndSelection',
            'required' => false,
        ]);
        $this->add($inputFilter, 'evaluation_entity_report_window');
    }
}
