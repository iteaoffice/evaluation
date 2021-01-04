<?php
/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation;

use Evaluation\Controller;

return [
    'router' => [
        'routes' => [
            'json' => [
                'type'          => 'Literal',
                'options'       => [
                    'route' => '/json',
                ],
                'may_terminate' => false,
                'child_routes'  => [
                    'evaluation' => [
                        'type'          => 'Literal',
                        'options'       => [
                            'route'    => '/evaluation',
                            'defaults' => [
                                'controller' => Controller\JsonController::class,
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'evaluation'        => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/evaluation.json',
                                    'defaults' => [
                                        'action'    => 'evaluation',
                                        'privilege' => 'overview',
                                    ],
                                ],
                            ],
                            'update-evaluation' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/update-evaluation.json',
                                    'defaults' => [
                                        'action'    => 'update-evaluation',
                                        'privilege' => 'update-evaluation',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
