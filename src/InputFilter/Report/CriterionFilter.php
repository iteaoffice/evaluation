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

namespace Evaluation\InputFilter\Report;

use Laminas\InputFilter\InputFilter;

/**
 * Class CriterionFilter
 * @package Evaluation\InputFilter\Report
 */
final class CriterionFilter extends InputFilter
{
    public function __construct()
    {
        $inputFilter = new InputFilter();
        $inputFilter->add(
            [
                'name'     => 'criterion',
                'required' => true,
                'filters'  => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
            ]
        );
        $inputFilter->add(
            [
                'name'     => 'sequence',
                'required' => true,
            ]
        );
        $inputFilter->add(
            [
                'name'     => 'type',
                'required' => true,
            ]
        );
        $inputFilter->add(
            [
                'name'     => 'topic',
                'required' => false,
            ]
        );
        $inputFilter->add(
            [
                'name'     => 'sequence',
                'required' => false,
            ]
        );
        $this->add($inputFilter, 'evaluation_entity_report_criterion');
    }
}
