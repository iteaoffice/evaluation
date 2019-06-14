<?php
/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @category    Evaluation
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2019 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/evaluation for the canonical source repository
 */
declare(strict_types=1);

namespace Evaluation;

use BjyAuthorize\Guard\Route;
use Evaluation\Acl\Assertion\ReportAssertion;

return [
    'bjyauthorize' => [
        'guards' => [
            /* If this guard is specified here (i.e. it is enabled], it will block
             * access to all routes unless they are specified here.
             */
            Route::class => [
                [
                    'route'     => 'community/evaluation/report2/list',
                    'roles'     => [],
                    'assertion' => ReportAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/report2/view',
                    'roles'     => [],
                    'assertion' => ReportAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/report2/update',
                    'roles'     => [],
                    'assertion' => ReportAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/report2/finalise',
                    'roles'     => [],
                    'assertion' => ReportAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/report2/create-from-report-review',
                    'roles'     => [],
                    'assertion' => ReportAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/report2/create-from-version-review',
                    'roles'     => [],
                    'assertion' => ReportAssertion::class,
                ],
                [
                    'route' => 'community/evaluation/report2/download-combined',
                    'roles' => ['user'],
                ],
            ],
        ],
    ],
];
