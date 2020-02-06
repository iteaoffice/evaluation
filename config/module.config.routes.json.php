<?php

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
