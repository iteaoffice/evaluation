<?php
declare(strict_types=1);

namespace Evaluation;

return [
    'navigation' => [
        'admin' => [
            'config'  => [
                'pages' => [
                    'report2-criterion-category' => [
                        'label' => _("txt-nav-evaluation-report-criterion-category-list"),
                        'route' => 'zfcadmin/evaluation/report2/criterion/category/list',
                        'pages' => [
                            'view' => [
                                'route'   => 'zfcadmin/evaluation/report2/criterion/category/view',
                                'visible' => false,
                                'params'  => [
                                    'entities'   => [
                                        'id' => Entity\Report\Criterion\Category::class,
                                    ],
                                    'invokables' => [
                                        Navigation\Invokable\Report\Criterion\CategoryLabel::class,
                                    ],
                                ],
                                'pages'   => [
                                    'edit' => [
                                        'label'   => _('txt-nav-edit'),
                                        'route'   => 'zfcadmin/evaluation/report2/criterion/category/edit',
                                        'visible' => false,
                                        'params'  => [
                                            'entities' => [
                                                'id' => Entity\Report\Criterion\Category::class,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'new'  => [
                                'label' => _('txt-nav-new-evaluation-report-criterion-category'),
                                'route' => 'zfcadmin/evaluation/report2/criterion/category/new',
                            ],
                        ],
                    ],
                    'report2-criterion-type'     => [
                        'label' => _("txt-nav-evaluation-report-criterion-type-list"),
                        'route' => 'zfcadmin/evaluation/report2/criterion/type/list',
                        'pages' => [
                            'view' => [
                                'route'   => 'zfcadmin/evaluation/report2/criterion/type/view',
                                'visible' => false,
                                'params'  => [
                                    'entities'   => [
                                        'id' => Entity\Report\Criterion\Type::class,
                                    ],
                                    'invokables' => [
                                        Navigation\Invokable\Report\Criterion\TypeLabel::class,
                                    ],
                                ],
                                'pages'   => [
                                    'edit' => [
                                        'label'   => _('txt-nav-edit'),
                                        'route'   => 'zfcadmin/evaluation/report2/criterion/type/edit',
                                        'visible' => false,
                                        'params'  => [
                                            'entities' => [
                                                'id' => Entity\Report\Criterion\Type::class,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'new'  => [
                                'label' => _('txt-nav-new-evaluation-report-criterion-type'),
                                'route' => 'zfcadmin/evaluation/report2/criterion/type/new',
                            ],
                        ],
                    ],
                    'report2-criterion-topic'    => [
                        'label' => _("txt-nav-evaluation-report-criterion-topic-list"),
                        'route' => 'zfcadmin/evaluation/report2/criterion/topic/list',
                        'pages' => [
                            'view' => [
                                'route'   => 'zfcadmin/evaluation/report2/criterion/topic/view',
                                'visible' => false,
                                'params'  => [
                                    'entities'   => [
                                        'id' => Entity\Report\Criterion\Topic::class,
                                    ],
                                    'invokables' => [
                                        Navigation\Invokable\Report\Criterion\TopicLabel::class,
                                    ],
                                ],
                                'pages'   => [
                                    'edit' => [
                                        'label'   => _('txt-nav-edit'),
                                        'route'   => 'zfcadmin/evaluation/report2/criterion/topic/edit',
                                        'visible' => false,
                                        'params'  => [
                                            'entities' => [
                                                'id' => Entity\Report\Criterion\Topic::class,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'new'  => [
                                'label' => _('txt-nav-new-evaluation-report-criterion-topic'),
                                'route' => 'zfcadmin/evaluation/report2/criterion/topic/new',
                            ],
                        ],
                    ],
                    'report2-criterion'          => [
                        'label' => _("txt-nav-evaluation-report-criterion-list"),
                        'route' => 'zfcadmin/evaluation/report2/criterion/list',
                        'pages' => [
                            'view' => [
                                'route'   => 'zfcadmin/evaluation/report2/criterion/view',
                                'visible' => false,
                                'params'  => [
                                    'entities'   => [
                                        'id' => \Project\Entity\Evaluation\Report2\Criterion::class,
                                    ],
                                    'invokables' => [
                                        \Project\Navigation\Invokable\Evaluation\Report2\CriterionLabel::class,
                                    ],
                                ],
                                'pages'   => [
                                    'edit' => [
                                        'label'   => _('txt-nav-edit'),
                                        'route'   => 'zfcadmin/evaluation/report2/criterion/edit',
                                        'visible' => false,
                                        'params'  => [
                                            'entities' => [
                                                'id' => \Project\Entity\Evaluation\Report2\Criterion::class,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'new'  => [
                                'label' => _('txt-nav-new-evaluation-report-criterion'),
                                'route' => 'zfcadmin/evaluation/report2/criterion/new',
                            ],
                        ],
                    ],
                    'report2-version'            => [
                        'label' => _("txt-nav-evaluation-report-version-list"),
                        'route' => 'zfcadmin/evaluation/report2/version/list',
                        'pages' => [
                            'view' => [
                                'route'   => 'zfcadmin/evaluation/report2/version/view',
                                'visible' => false,
                                'params'  => [
                                    'entities'   => [
                                        'id' => Entity\Report\Version::class,
                                    ],
                                    'invokables' => [
                                        Navigation\Invokable\Report\VersionLabel::class,
                                    ],
                                ],
                                'pages'   => [
                                    'edit' => [
                                        'label'   => _('txt-nav-edit'),
                                        'route'   => 'zfcadmin/evaluation/report2/version/edit',
                                        'visible' => false,
                                        'params'  => [
                                            'entities' => [
                                                'id' => Entity\Report\Version::class,
                                            ],
                                        ],
                                    ],
                                    'copy' => [
                                        'label'   => _('txt-nav-copy'),
                                        'route'   => 'zfcadmin/evaluation/report2/version/copy',
                                        'visible' => false,
                                        'params'  => [
                                            'entities' => [
                                                'id' => Entity\Report\Version::class,
                                            ],
                                        ],
                                    ],
                                    'view-criterion-version' => [
                                        'label'   => _('txt-nav-view'),
                                        'route'   => 'zfcadmin/evaluation/report2/criterion/version/view',
                                        'visible' => false,
                                        'params'  => [
                                            'entities' => [
                                                'id' => Entity\Report\Criterion\Version::class,
                                            ],
                                            'invokables' => [
                                                Navigation\Invokable\Report\Criterion\VersionLabel::class,
                                            ],
                                        ],
                                        'pages'   => [
                                            'edit' => [
                                                'label'   => _('txt-nav-edit'),
                                                'route'   => 'zfcadmin/evaluation/report2/criterion/version/edit',
                                                'visible' => false,
                                                'params'  => [
                                                    'entities' => [
                                                        'id' => Entity\Report\Criterion\Version::class,
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'add-criterion-version'  => [
                                        'label' => _('txt-add-new-evaluation-report-criterion'),
                                        'route' => 'zfcadmin/evaluation/report2/criterion/version/add',
                                        'visible' => false,
                                        'params'  => [
                                            'entities'   => [
                                                'id' => Entity\Report\Version::class,
                                            ],
                                            'routeParam' => [
                                                'id' => 'reportVersionId',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'new'  => [
                                'label' => _('txt-nav-new-evaluation-report-version'),
                                'route' => 'zfcadmin/evaluation/report2/version/new',
                            ],
                        ],
                    ],
                    'report2-window'             => [
                        'label' => _('txt-nav-evaluation-report-window-list'),
                        'route' => 'zfcadmin/evaluation/report2/window/list',
                        'pages' => [
                            'view' => [
                                'route'   => 'zfcadmin/evaluation/report2/window/view',
                                'visible' => false,
                                'params'  => [
                                    'entities'   => [
                                        'id' => Entity\Report\Window::class,
                                    ],
                                    'invokables' => [
                                        Navigation\Invokable\Report\WindowLabel::class,
                                    ],
                                ],
                                'pages'   => [
                                    'edit' => [
                                        'label'   => _('txt-nav-edit'),
                                        'route'   => 'zfcadmin/evaluation/report2/window/edit',
                                        'visible' => false,
                                        'params'  => [
                                            'entities' => [
                                                'id' => Entity\Report\Window::class,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'new'  => [
                                'label' => _('txt-nav-new-evaluation-report-window'),
                                'route' => 'zfcadmin/evaluation/report2/window/new',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];