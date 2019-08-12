<?php

declare(strict_types=1);

namespace Evaluation;

use Program\Entity\Call\Call;
use Project\Entity\Project;
use Project\Entity\Version\Reviewer;

return [
    'navigation' => [
        'community2'  => [
            'index' => [
                'pages' => [
                    'project'    => [
                        'pages' => [
                            /*'project-basics' => [
                                'pages' => [
                                    'project-version' => [
                                        'pages' => [
                                            'create-project-version-evaluation-report' => [
                                                'route'   => 'community/evaluation/report/create-from-version-review',
                                                'visible' => false,
                                                'label'   => _('txt-new-evaluation-report'),
                                                'params'  => [
                                                    'entities'   => [
                                                        'id' => Reviewer::class,
                                                    ],
                                                    'routeParam' => [
                                                        'id' => 'versionReviewer',
                                                    ],
                                                    'invokables' => [
                                                        Navigation\Invokable\ReportLabel::class,
                                                    ],
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],*/
                            'report'        => [
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
                    /*'evaluation' => [
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
                        ],
                    ],*/
                ],
            ],
        ],
    ],
];