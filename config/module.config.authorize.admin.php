<?php
declare(strict_types=1);

namespace Evaluation;

use BjyAuthorize\Guard\Route;

return [
    'bjyauthorize' => [
        // resource providers provide a list of resources that will be tracked
        // in the ACL. like roles, they can be hierarchical
        'guards' => [
            /* If this guard is specified here (i.e. it is enabled], it will block
             * access to all routes unless they are specified here.
             */
            Route::class => [
                [
                    'route' => 'zfcadmin/evaluation/reviewer',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/reviewer/roster',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/reviewer/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/reviewer/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/reviewer/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/reviewer/delete',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/reviewer/contact',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/reviewer/contact/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/reviewer/contact/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/reviewer/contact/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/reviewer/contact/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/project/evaluation/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/project/evaluation/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/matrix',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/migrate',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/upload',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/download',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/presentation',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/create-from-report',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/create-from-version',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/update',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/finalise',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/undo-final',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/category/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/category/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/category/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/category/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/type/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/type/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/type/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/type/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/topic/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/topic/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/topic/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/topic/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/window/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/window/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/window/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/window/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/version/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/version/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/version/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/version/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/version/copy',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/version/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/version/add',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report/criterion/version/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/feedback/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/feedback/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/feedback/edit',
                    'roles' => ['office'],
                ],

            ],
        ],
    ],
];
