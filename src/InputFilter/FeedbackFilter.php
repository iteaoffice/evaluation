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

namespace Evaluation\InputFilter;

use Zend\InputFilter\InputFilter;

/**
 * Class FeedbackFilter
 * @package Evaluation\InputFilter
 */
final class FeedbackFilter extends InputFilter
{
    public function __construct()
    {
        $inputFilter = new InputFilter();
        $inputFilter->add(
            [
                'name'     => 'parentType',
                'required' => false,
            ]
        );

        $inputFilter->add(
            [
                'name'     => 'reviewFeedback',
                'required' => false,
                'filters'  => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
            ]
        );

        $inputFilter->add(
            [
                'name'     => 'evaluationFeedback',
                'required' => false,
                'filters'  => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],

            ]
        );

        $inputFilter->add(
            [
                'name'     => 'status',
                'required' => true,

            ]
        );

        $this->add($inputFilter, 'evaluation_entity_feedback');
    }
}
