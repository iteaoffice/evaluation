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

return [
    'navigation' => [
        'admin' => [
            'tools'   => [
                'pages' => [
                    'review-schedule' => [
                        'label' => _('txt-nav-review-schedule'),
                        'route' => 'zfcadmin/evaluation/review-schedule',
                    ],
                ],
            ],
            'config'  => [
                'pages' => [
                    'report-criterion-category' => [
                        'label' => _("txt-nav-evaluation-report-criterion-category-list"),
                        'route' => 'zfcadmin/evaluation/report/criterion/category/list',
                        'pages' => [
                            'view' => [
                                'route'   => 'zfcadmin/evaluation/report/criterion/category/view',
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
                                        'route'   => 'zfcadmin/evaluation/report/criterion/category/edit',
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
                                'route' => 'zfcadmin/evaluation/report/criterion/category/new',
                            ],
                        ],
                    ],
                    'report-criterion-type'     => [
                        'label' => _("txt-nav-evaluation-report-criterion-type-list"),
                        'route' => 'zfcadmin/evaluation/report/criterion/type/list',
                        'pages' => [
                            'view' => [
                                'route'   => 'zfcadmin/evaluation/report/criterion/type/view',
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
                                        'route'   => 'zfcadmin/evaluation/report/criterion/type/edit',
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
                                'route' => 'zfcadmin/evaluation/report/criterion/type/new',
                            ],
                        ],
                    ],
                    'report-criterion-topic'    => [
                        'label' => _("txt-nav-evaluation-report-criterion-topic-list"),
                        'route' => 'zfcadmin/evaluation/report/criterion/topic/list',
                        'pages' => [
                            'view' => [
                                'route'   => 'zfcadmin/evaluation/report/criterion/topic/view',
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
                                        'route'   => 'zfcadmin/evaluation/report/criterion/topic/edit',
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
                                'route' => 'zfcadmin/evaluation/report/criterion/topic/new',
                            ],
                        ],
                    ],
                    'report-criterion'          => [
                        'label' => _("txt-nav-evaluation-report-criterion-list"),
                        'route' => 'zfcadmin/evaluation/report/criterion/list',
                        'pages' => [
                            'view' => [
                                'route'   => 'zfcadmin/evaluation/report/criterion/view',
                                'visible' => false,
                                'params'  => [
                                    'entities'   => [
                                        'id' => Entity\Report\Criterion::class,
                                    ],
                                    'invokables' => [
                                        Navigation\Invokable\Report\CriterionLabel::class,
                                    ],
                                ],
                                'pages'   => [
                                    'edit' => [
                                        'label'   => _('txt-nav-edit'),
                                        'route'   => 'zfcadmin/evaluation/report/criterion/edit',
                                        'visible' => false,
                                        'params'  => [
                                            'entities' => [
                                                'id' => Entity\Report\Criterion::class,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'new'  => [
                                'label' => _('txt-nav-new-evaluation-report-criterion'),
                                'route' => 'zfcadmin/evaluation/report/criterion/new',
                            ],
                        ],
                    ],
                    'report-version'            => [
                        'label' => _("txt-nav-evaluation-report-version-list"),
                        'route' => 'zfcadmin/evaluation/report/version/list',
                        'pages' => [
                            'view' => [
                                'route'   => 'zfcadmin/evaluation/report/version/view',
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
                                        'route'   => 'zfcadmin/evaluation/report/version/edit',
                                        'visible' => false,
                                        'params'  => [
                                            'entities' => [
                                                'id' => Entity\Report\Version::class,
                                            ],
                                        ],
                                    ],
                                    'copy' => [
                                        'label'   => _('txt-nav-copy'),
                                        'route'   => 'zfcadmin/evaluation/report/version/copy',
                                        'visible' => false,
                                        'params'  => [
                                            'entities' => [
                                                'id' => Entity\Report\Version::class,
                                            ],
                                        ],
                                    ],
                                    'view-criterion-version' => [
                                        'label'   => _('txt-nav-view'),
                                        'route'   => 'zfcadmin/evaluation/report/criterion/version/view',
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
                                                'route'   => 'zfcadmin/evaluation/report/criterion/version/edit',
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
                                        'route' => 'zfcadmin/evaluation/report/criterion/version/add',
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
                                'route' => 'zfcadmin/evaluation/report/version/new',
                            ],
                        ],
                    ],
                    'report-window'             => [
                        'label' => _('txt-nav-evaluation-report-window-list'),
                        'route' => 'zfcadmin/evaluation/report/window/list',
                        'pages' => [
                            'view' => [
                                'route'   => 'zfcadmin/evaluation/report/window/view',
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
                                        'route'   => 'zfcadmin/evaluation/report/window/edit',
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
                                'route' => 'zfcadmin/evaluation/report/window/new',
                            ],
                        ],
                    ],
                    'review-contact-list'        => [
                        'label' => _('txt-nav-review-contact-list'),
                        'route' => 'zfcadmin/evaluation/reviewer/contact/list',
                        'pages' => [
                            'view' => [
                                'route'   => 'zfcadmin/evaluation/reviewer/contact/view',
                                'visible' => false,
                                'params'  => [
                                    'entities'   => [
                                        'id' => Entity\Reviewer\Contact::class,
                                    ],
                                    'invokables' => [
                                        Navigation\Invokable\Reviewer\ContactLabel::class,
                                    ],
                                ],
                                'pages'   => [
                                    'edit' => [
                                        'label'   => _('txt-nav-edit'),
                                        'route'   => 'zfcadmin/evaluation/reviewer/contact/edit',
                                        'visible' => false,
                                        'params'  => [
                                            'entities' => [
                                                'id' => Entity\Reviewer\Contact::class,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'new'  => [
                                'label' => _('txt-nav-new-review-contact'),
                                'route' => 'zfcadmin/evaluation/reviewer/contact/new',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
