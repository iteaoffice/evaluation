<?php

declare(strict_types=1);

namespace Evaluation;

use Evaluation\Controller;

return [
    'router' => [
        'routes' => [
            'json'      => [
                'type'          => 'Literal',
                'options'       => [
                    'route'    => '/json',
                    'defaults' => [
                        'controller' => Controller\JsonController::class,
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes'  => [
                    'evaluation'                       => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => '/evaluation.json',
                            'defaults' => [
                                'action'    => 'evaluation',
                                'privilege' => 'overview',
                            ],
                        ],
                    ],
                    'update-evaluation'                => [
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
            'community' => [
                'child_routes'  => [
                    'evaluation' => [
                        'type'          => 'Segment',
                        'priority'      => 1000,
                        'options'       => [
                            'route'    => '/evaluation',
                            'defaults' => [
                                'controller' => Controller\EvaluationController::class,
                                'action'     => 'overview',
                                'privilege'  => 'overview',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'index'            => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/index.html',
                                    'defaults' => [
                                        'action'    => 'index',
                                        'privilege' => 'index',
                                    ],
                                ],
                            ],
                            'overview'         => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/overview[/:show][/source-:source]/call-[:call][/type-[:type[/display-:display]]].html',
                                    'defaults' => [
                                        'action'    => 'overview',
                                        'privilege' => 'overview',
                                    ],
                                ],
                            ],
                            'download'         => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/download/type-[:type]/call-[:call].xlsx',
                                    'constraints' => [
                                        'call' => '\d+',
                                    ],
                                    'defaults'    => [
                                        'action'    => 'download-overview',
                                        'privilege' => 'download-overview',
                                    ],
                                ],
                            ],
                            'download-project' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/download/country-[:country]/type-[:type]/project-[:project].pdf',
                                    'constraints' => [
                                        'call' => '\d+',
                                    ],
                                    'defaults'    => [
                                        'action'    => 'download-project',
                                        'privilege' => 'download-project',
                                    ],
                                ],
                            ],
                            'evaluate-project' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/evaluate/country-[:country]/type-[:type]/project-[:project].html',
                                    'defaults' => [
                                        'action'    => 'evaluate-project',
                                        'privilege' => 'evaluate-project',
                                    ],
                                ],
                            ],
                            'overview-project' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/overview/country-[:country]/type-[:type]/project-[:project].html',
                                    'defaults' => [
                                        'action'    => 'overview-project',
                                        'privilege' => 'overview-project',
                                    ],
                                ],
                            ],
                            'report'           => [
                                'type'          => 'Segment',
                                'options'       => [
                                    'route'    => '/report',
                                    'defaults' => [
                                        'controller' => Controller\ReportController::class,
                                        'action'     => 'list',
                                    ],
                                ],
                                'may_terminate' => false,
                                'child_routes'  => [
                                    'list'                       => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/list.html',
                                            'defaults' => [
                                                'action'    => 'list',
                                                'privilege' => 'list',
                                            ],
                                        ],
                                    ],
                                    'view'                       => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/view/[:id].html',
                                            'defaults' => [
                                                'action'    => 'view',
                                                'privilege' => 'view',
                                            ],
                                        ],
                                    ],
                                    'update'                     => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/update/[:id].html',
                                            'defaults' => [
                                                'action'    => 'edit',
                                                'privilege' => 'update',
                                            ],
                                        ],
                                    ],
                                    'finalise'                   => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/finalise/[:id].html',
                                            'defaults' => [
                                                'action'    => 'finalise',
                                                'privilege' => 'update',
                                            ],
                                        ],
                                    ],
                                    'create-from-version-review' => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/create/version-[:versionReviewer].html',
                                            'defaults' => [
                                                'action'    => 'new',
                                                'privilege' => 'create',
                                            ],
                                        ],
                                    ],
                                    'create-from-report-review'  => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/create/report-[:reportReviewer].html',
                                            'defaults' => [
                                                'action'    => 'new',
                                                'privilege' => 'create',
                                            ],
                                        ],
                                    ],
                                    'download-combined'          => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/download-combined/status-[:status].zip',
                                            'defaults' => [
                                                'action' => 'download-combined',
                                            ],
                                        ],
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
