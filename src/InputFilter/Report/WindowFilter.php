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

namespace Evaluation\InputFilter\Report;

use Zend\InputFilter\InputFilter;

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
