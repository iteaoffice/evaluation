<?php

declare(strict_types=1);

namespace Evaluation;

use Program\Entity\Call\Call;
use Project\Entity\Project;
use Project\Entity\Version\Review;

return [
    'navigation' => [
        'community'  => [
            'project'    => [
                'order' => 10,
                'label' => _('txt-projects'),
                'route' => 'community/project/list',
                'pages' => [
                    'project-basics' => [
                        'pages' => [
                            'project-version' => [
                                'pages' => [
                                    'create-project-version-evaluation-report2' => [
                                        'route'   => 'community/evaluation/report2/create-from-version-review',
                                        'visible' => false,
                                        'label'   => _('txt-new-evaluation-report'),
                                        'params'  => [
                                            'entities'   => [
                                                'id' => Review::class,
                                            ],
                                            'routeParam' => [
                                                'id' => 'versionReview',
                                            ],
                                            'invokables' => [
                                                Navigation\Invokable\ReportLabel::class,
                                            ],
                                        ],
                                    ],
                                ]
                            ],
                        ],
                    ],
                    'report2'        => [
                        'label'     => _('txt-stg-evaluation-template'),
                        'route'     => 'community/evaluation/report2/list',
                        'resource'  => 'route/community/evaluation/report2/list',
                        'privilege' => 'list',
                        'visible'   => true,
                        'pages'     => [
                            'view' => [
                                'route'   => 'community/evaluation/report2/view',
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
                                        'route'   => 'community/evaluation/report2/update',
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
                                        Navigation\Invokable\EvaluationLabel::class,
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
                                        Navigation\Invokable\EvaluateProjectLabel::class,
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
                    'report2'   => [
                        'label'     => _('txt-evaluation-reports'),
                        'route'     => 'community/evaluation/report2/list',
                        'resource'  => 'route/community/evaluation/report2/list',
                        'privilege' => 'list',
                        'visible'   => true,
                        'pages'     => [
                            'view' => [
                                'route'   => 'community/evaluation/report2/view',
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
                                        'route'   => 'community/evaluation/report2/update',
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
];