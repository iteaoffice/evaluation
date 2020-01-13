<?php

declare(strict_types=1);

namespace Evaluation;

use Evaluation\Entity\Report;
use Evaluation\Navigation\Invokable;
use Program\Entity\Call\Call;
use Project\Entity\Project;

return [
    'navigation' => [
        'community'  => [
            'evaluation' => [
                'order'     => 20,
                'label'     => _('txt-project-evaluation'),
                'route'     => 'community/evaluation/index',
                'resource'  => 'route/community/evaluation/index',
                'privilege' => 'index',
                'pages'     => [
                    'overview' => [
                        'label'     => _('txt-evaluation-overview'),
                        'route'     => 'community/evaluation/index',
                        'resource'  => 'route/community/evaluation/index',
                        'privilege' => 'index',
                        'pages'     => [
                            'overview'         => [
                                'label'     => _('txt-evaluation-overview'),
                                'route'     => 'community/evaluation/overview',
                                'resource'  => 'route/community/evaluation/overview',
                                'privilege' => 'overview',
                                'params'    => [
                                    'entities'   => [
                                        'id' => Call::class,
                                    ],
                                    'routeParam' => [
                                        'id' => 'call',
                                    ],
                                    'invokables' => [
                                        Invokable\EvaluationLabel::class,
                                    ],
                                ],
                            ],
                            'overview-project' => [
                                'label'   => _('txt-overview-project'),
                                'route'   => 'community/evaluation/overview-project',
                                'visible' => false,
                                'params'  => [
                                    'entities'   => [
                                        'id' => Project::class,
                                    ],
                                    'routeParam' => [
                                        'id' => 'project',
                                    ],
                                    'invokables' => [
                                        Invokable\EvaluateProjectLabel::class,
                                    ],
                                ],
                                'pages'   => [
                                    'evaluate-project' => [
                                        'label'   => _('txt-evaluate-project'),
                                        'route'   => 'community/evaluation/evaluate-project',
                                        'visible' => false,
                                        'params'  => [
                                            'entities'   => [
                                                'id' => Project::class,
                                            ],
                                            'routeParam' => [
                                                'id' => 'project',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'report'   => [
                        'label'     => _('txt-evaluation-reports'),
                        'route'     => 'community/evaluation/report/list',
                        'resource'  => 'route/community/evaluation/report/list',
                        'privilege' => 'list',
                        'visible'   => true,
                        'pages'     => [
                            'view' => [
                                'route'   => 'community/evaluation/report/view',
                                'visible' => false,
                                'params'  => [
                                    'entities'   => [
                                        'id' => Report::class,
                                    ],
                                    'invokables' => [
                                        Invokable\ReportLabel::class
                                    ],
                                ],
                                'pages'   => [
                                    'update' => [
                                        'label'   => _('txt-nav-update'),
                                        'route'   => 'community/evaluation/report/update',
                                        'visible' => false,
                                        'params'  => [
                                            'entities' => [
                                                'id' => Report::class,
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
        'community2' => [
            'index' => [
                'pages' => [
                    'project' => [
                        'pages' => [
                            'report' => [
                                'label'     => _('txt-stg-evaluation-template'),
                                'route'     => 'community/evaluation/report/list',
                                'resource'  => 'route/community/evaluation/report/list',
                                'privilege' => 'list',
                                'visible'   => true,
                                'pages'     => [
                                    'view' => [
                                        'route'   => 'community/evaluation/report/view',
                                        'visible' => false,
                                        'params'  => [
                                            'entities'   => [
                                                'id' => Entity\Report::class,
                                            ],
                                            'invokables' => [
                                                Navigation\Invokable\ReportLabel::class
                                            ],
                                        ],
                                        'pages'   => [
                                            'update' => [
                                                'label'   => _('txt-nav-update'),
                                                'route'   => 'community/evaluation/report/update',
                                                'visible' => false,
                                                'params'  => [
                                                    'entities' => [
                                                        'id' => Entity\Report::class,
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
        ],
    ],
];
