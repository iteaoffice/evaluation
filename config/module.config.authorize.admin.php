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
                    'route' => 'zfcadmin/evaluation/report2/upload',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/download',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/create-from-report',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/create-from-version',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/update',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/finalise',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/undo-final',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/category/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/category/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/category/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/category/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/type/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/type/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/type/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/type/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/topic/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/topic/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/topic/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/topic/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/window/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/window/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/window/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/window/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/version/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/version/new',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/version/list',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/version/view',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/version/copy',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/version/edit',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/version/add',
                    'roles' => ['office'],
                ],
                [
                    'route' => 'zfcadmin/evaluation/report2/criterion/version/view',
                    'roles' => ['office'],
                ],
            ],
        ],
    ],
];
