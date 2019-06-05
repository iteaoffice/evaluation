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
 * Class ResultFilter
 * @package Evaluation\InputFilter\Report
 */
final class ResultFilter extends InputFilter
{
    public function __construct()
    {
        $this->add([
            'name'     => 'comment',
            'required' => false,
            'filters'  => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
                ['name' => 'ToNull'],
            ],
        ]);

        $this->add([
            'name'     => 'value',
            'required' => false,
            'filters'  => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
                ['name' => 'ToNull'],
            ],
        ]);

        $this->add([
            'name'     => 'score',
            'required' => false,
            'filters'  => [
                ['name' => 'ToInt'],
            ],
        ]);
    }
}
