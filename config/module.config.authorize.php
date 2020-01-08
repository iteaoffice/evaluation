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
use Evaluation\Acl\Assertion\EvaluationAssertion;
use Evaluation\Acl\Assertion\ReportAssertion;
use Project\Acl\Assertion\Project as ProjectAssertion;

return [
    'bjyauthorize' => [
        'guards' => [
            Route::class => [
                [
                    'route'     => 'community/evaluation/index',
                    'roles'     => ['user'],
                    'assertion' => EvaluationAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/overview',
                    'roles'     => ['user'],
                    'assertion' => EvaluationAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/download',
                    'roles'     => [],
                    'assertion' => EvaluationAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/download-project',
                    'roles'     => [],
                    'assertion' => EvaluationAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/evaluate-project',
                    'roles'     => [],
                    'assertion' => EvaluationAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/overview-project',
                    'roles'     => [],
                    'assertion' => EvaluationAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/report/list',
                    'roles'     => [],
                    'assertion' => ReportAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/report/view',
                    'roles'     => [],
                    'assertion' => ReportAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/report/update',
                    'roles'     => [],
                    'assertion' => ReportAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/report/finalise',
                    'roles'     => [],
                    'assertion' => ReportAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/report/create-from-report-review',
                    'roles'     => [],
                    'assertion' => ReportAssertion::class,
                ],
                [
                    'route'     => 'community/evaluation/report/create-from-version-review',
                    'roles'     => [],
                    'assertion' => ReportAssertion::class,
                ],
                [
                    'route' => 'community/evaluation/report/download-combined',
                    'roles' => ['user'],
                ],
                [
                    'route'     => 'json/evaluation/evaluation',
                    'roles'     => [],
                    'assertion' => EvaluationAssertion::class,
                ],
                [
                    'route'     => 'json/evaluation/update-evaluation',
                    'roles'     => [],
                    'assertion' => ProjectAssertion::class,
                ],
            ],
        ],
    ],
];
