<?php
/**
 * ITEA Office copyright message placeholder
 *
 * @category    Contact
 * @package     Config
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2017 ITEA Office (https://itea3.org)
 */

use Evaluation\Controller;
use Zend\Router\Http\Literal;
use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'zfcadmin' => [
                'child_routes' => [
                    'evaluation' => [
                        'type'          => Segment::class,
                        'options'       => [
                            'route'    => '/evaluation',
                            'defaults' => [
                                'controller' => Controller\EvaluationManagerController::class,
                            ],
                        ],
                        'may_terminate' => false,
                        'child_routes'  => [
                            'matrix'   => [
                                'type'    => Segment::class,
                                'options' => [
                                    'route'    => '/matrix[/source-:source][/call-:call[/type-[:type[/display-:display]]]].html',
                                    'defaults' => [
                                        'action' => 'matrix',
                                    ],
                                ],
                            ],
                            'report'   => [
                                'type'          => Literal::class,
                                'options'       => [
                                    'route'    => '/report',
                                    'defaults' => [
                                        'action'     => 'list',
                                        'controller' => Controller\ReportManagerController::class,
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes'  => [
                                    'migrate'                => [
                                        'type'     => Segment::class,
                                        'priority' => 1000,
                                        'options'  => [
                                            'route'    => '/migrate.html',
                                            'defaults' => [
                                                'action' => 'migrate',
                                            ],
                                        ],
                                    ],
                                    'list'                => [
                                        'type'     => Segment::class,
                                        'priority' => 1000,
                                        'options'  => [
                                            'route'    => '/list[/f-:encodedFilter][/page-:page].html',
                                            'defaults' => [
                                                'action' => 'list',
                                            ],
                                        ],
                                    ],
                                    'view'                => [
                                        'type'    => Segment::class,
                                        'options' => [
                                            'route'    => '/view/[:id].html',
                                            'defaults' => [
                                                'action' => 'view',

                                            ],
                                        ],
                                    ],
                                    'update'              => [
                                        'type'    => Segment::class,
                                        'options' => [
                                            'route'    => '/update/[:id].html',
                                            'defaults' => [
                                                'action' => 'edit-final',
                                            ],
                                        ],
                                    ],
                                    'finalise'            => [
                                        'type'    => Segment::class,
                                        'options' => [
                                            'route'    => '/finalise/[:id].html',
                                            'defaults' => [
                                                'action' => 'finalise',
                                            ],
                                        ],
                                    ],
                                    'undo-final'          => [
                                        'type'    => Segment::class,
                                        'options' => [
                                            'route'    => '/undo-final/[:id].html',
                                            'defaults' => [
                                                'action' => 'undo-final',

                                            ],
                                        ],
                                    ],
                                    'create-from-report'  => [
                                        'type'    => Segment::class,
                                        'options' => [
                                            'route'    => '/create/report-[:report].html',
                                            'defaults' => [
                                                'action' => 'new-final',
                                            ],
                                        ],
                                    ],
                                    'create-from-version' => [
                                        'type'    => Segment::class,
                                        'options' => [
                                            'route'    => '/create/version-[:version].html',
                                            'defaults' => [
                                                'action' => 'new-final',
                                            ],
                                        ],
                                    ],
                                    'download'            => [
                                        'type'    => Segment::class,
                                        'options' => [
                                            'route'    => '/download/[:id].html',
                                            'defaults' => [
                                                'action' => 'download',

                                            ],
                                        ],
                                    ],
                                    'version'             => [
                                        'type'          => Literal::class,
                                        'options'       => [
                                            'route'    => '/version',
                                            'defaults' => [
                                                'action'     => 'list',
                                                'controller' => Controller\Report\VersionController::class,
                                            ],
                                        ],
                                        'may_terminate' => false,
                                        'child_routes'  => [
                                            'list' => [
                                                'type'     => Segment::class,
                                                'priority' => 1000,
                                                'options'  => [
                                                    'route'    => '/list[/f-:encodedFilter][/page-:page].html',
                                                    'defaults' => [
                                                        'action' => 'list',
                                                    ],
                                                ],
                                            ],
                                            'view' => [
                                                'type'    => Segment::class,
                                                'options' => [
                                                    'route'    => '/view/[:id].html',
                                                    'defaults' => [
                                                        'action' => 'view',

                                                    ],
                                                ],
                                            ],
                                            'edit' => [
                                                'type'    => Segment::class,
                                                'options' => [
                                                    'route'    => '/edit/[:id].html',
                                                    'defaults' => [
                                                        'action' => 'edit',
                                                    ],
                                                ],
                                            ],
                                            'new'  => [
                                                'type'    => Segment::class,
                                                'options' => [
                                                    'route'    => '/new[/type-:typeId].html',
                                                    'defaults' => [
                                                        'action' => 'new',
                                                    ],
                                                ],
                                            ],
                                            'copy' => [
                                                'type'    => Segment::class,
                                                'options' => [
                                                    'route'    => '/copy/[:id].html',
                                                    'defaults' => [
                                                        'action' => 'copy',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'criterion'           => [
                                        'type'          => Literal::class,
                                        'options'       => [
                                            'route'    => '/criterion',
                                            'defaults' => [
                                                'action'     => 'list',
                                                'controller' => Controller\Report\CriterionController::class,
                                            ],
                                        ],
                                        'may_terminate' => false,
                                        'child_routes'  => [
                                            'list'     => [
                                                'type'     => Segment::class,
                                                'priority' => 1000,
                                                'options'  => [
                                                    'route'    => '/list[/f-:encodedFilter][/page-:page].html',
                                                    'defaults' => [
                                                        'action' => 'list',
                                                    ],
                                                ],
                                            ],
                                            'view'     => [
                                                'type'    => Segment::class,
                                                'options' => [
                                                    'route'    => '/view/[:id].html',
                                                    'defaults' => [
                                                        'action' => 'view',

                                                    ],
                                                ],
                                            ],
                                            'edit'     => [
                                                'type'    => Segment::class,
                                                'options' => [
                                                    'route'    => '/edit/[:id].html',
                                                    'defaults' => [
                                                        'action' => 'edit',
                                                    ],
                                                ],
                                            ],
                                            'new'      => [
                                                'type'    => Segment::class,
                                                'options' => [
                                                    'route'    => '/new[/type-:typeId].html',
                                                    'defaults' => [
                                                        'action' => 'new',
                                                    ],
                                                ],
                                            ],
                                            'category' => [
                                                'type'          => Segment::class,
                                                'options'       => [
                                                    'route'    => '/category',
                                                    'defaults' => [
                                                        'action'     => 'list',
                                                        'controller' => Controller\Report\Criterion\CategoryController::class,
                                                    ],
                                                ],
                                                'may_terminate' => true,
                                                'child_routes'  => [
                                                    'list' => [
                                                        'type'     => Segment::class,
                                                        'priority' => 1000,
                                                        'options'  => [
                                                            'route'    => '/list[/f-:encodedFilter][/page-:page].html',
                                                            'defaults' => [
                                                                'action' => 'list',
                                                            ],
                                                        ],
                                                    ],
                                                    'new'  => [
                                                        'type'    => Segment::class,
                                                        'options' => [
                                                            'route'    => '/new.html',
                                                            'defaults' => [
                                                                'action' => 'new',
                                                            ],
                                                        ],
                                                    ],
                                                    'view' => [
                                                        'type'    => Segment::class,
                                                        'options' => [
                                                            'route'    => '/view/[:id].html',
                                                            'defaults' => [
                                                                'action' => 'view',

                                                            ],
                                                        ],
                                                    ],
                                                    'edit' => [
                                                        'type'    => Segment::class,
                                                        'options' => [
                                                            'route'    => '/edit/[:id].html',
                                                            'defaults' => [
                                                                'action' => 'edit',
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            'type'     => [
                                                'type'          => Segment::class,
                                                'options'       => [
                                                    'route'    => '/type',
                                                    'defaults' => [
                                                        'action'     => 'list',
                                                        'controller' => Controller\Report\Criterion\TypeController::class,
                                                    ],
                                                ],
                                                'may_terminate' => true,
                                                'child_routes'  => [
                                                    'list' => [
                                                        'type'     => Segment::class,
                                                        'priority' => 1000,
                                                        'options'  => [
                                                            'route'    => '/list[/f-:encodedFilter][/page-:page].html',
                                                            'defaults' => [
                                                                'action' => 'list',
                                                            ],
                                                        ],
                                                    ],
                                                    'new'  => [
                                                        'type'    => Segment::class,
                                                        'options' => [
                                                            'route'    => '/new.html',
                                                            'defaults' => [
                                                                'action' => 'new',
                                                            ],
                                                        ],
                                                    ],
                                                    'view' => [
                                                        'type'    => Segment::class,
                                                        'options' => [
                                                            'route'    => '/view/[:id].html',
                                                            'defaults' => [
                                                                'action' => 'view',

                                                            ],
                                                        ],
                                                    ],
                                                    'edit' => [
                                                        'type'    => Segment::class,
                                                        'options' => [
                                                            'route'    => '/edit/[:id].html',
                                                            'defaults' => [
                                                                'action' => 'edit',
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            'topic'    => [
                                                'type'          => Segment::class,
                                                'options'       => [
                                                    'route'    => '/topic',
                                                    'defaults' => [
                                                        'action'     => 'list',
                                                        'controller' => Controller\Report\Criterion\TopicController::class,
                                                    ],
                                                ],
                                                'may_terminate' => true,
                                                'child_routes'  => [
                                                    'list' => [
                                                        'type'     => Segment::class,
                                                        'priority' => 1000,
                                                        'options'  => [
                                                            'route'    => '/list[/f-:encodedFilter][/page-:page].html',
                                                            'defaults' => [
                                                                'action' => 'list',
                                                            ],
                                                        ],
                                                    ],
                                                    'new'  => [
                                                        'type'    => Segment::class,
                                                        'options' => [
                                                            'route'    => '/new.html',
                                                            'defaults' => [
                                                                'action' => 'new',
                                                            ],
                                                        ],
                                                    ],
                                                    'view' => [
                                                        'type'    => Segment::class,
                                                        'options' => [
                                                            'route'    => '/view/[:id].html',
                                                            'defaults' => [
                                                                'action' => 'view',

                                                            ],
                                                        ],
                                                    ],
                                                    'edit' => [
                                                        'type'    => Segment::class,
                                                        'options' => [
                                                            'route'    => '/edit/[:id].html',
                                                            'defaults' => [
                                                                'action' => 'edit',
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            'version'  => [
                                                'type'          => Segment::class,
                                                'options'       => [
                                                    'route'    => '/version',
                                                    'defaults' => [
                                                        'controller' => Controller\Report\Criterion\VersionController::class,
                                                    ],
                                                ],
                                                'may_terminate' => true,
                                                'child_routes'  => [
                                                    'add'  => [
                                                        'type'    => Segment::class,
                                                        'options' => [
                                                            'route'    => '/add[/report-version-:reportVersionId].html',
                                                            'defaults' => [
                                                                'action' => 'add',
                                                            ],
                                                        ],
                                                    ],
                                                    'view' => [
                                                        'type'    => Segment::class,
                                                        'options' => [
                                                            'route'    => '/view/[:id].html',
                                                            'defaults' => [
                                                                'action' => 'view',

                                                            ],
                                                        ],
                                                    ],
                                                    'edit' => [
                                                        'type'    => Segment::class,
                                                        'options' => [
                                                            'route'    => '/edit/[:id].html',
                                                            'defaults' => [
                                                                'action' => 'edit',
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'window'              => [
                                        'type'          => Literal::class,
                                        'options'       => [
                                            'route'    => '/window',
                                            'defaults' => [
                                                'action'     => 'list',
                                                'controller' => Controller\Report\WindowController::class,
                                            ],
                                        ],
                                        'may_terminate' => false,
                                        'child_routes'  => [
                                            'list' => [
                                                'type'     => Segment::class,
                                                'priority' => 1000,
                                                'options'  => [
                                                    'route'    => '/list[/f-:encodedFilter][/page-:page].html',
                                                    'defaults' => [
                                                        'action' => 'list',
                                                    ],
                                                ],
                                            ],
                                            'new'  => [
                                                'type'    => Segment::class,
                                                'options' => [
                                                    'route'    => '/new.html',
                                                    'defaults' => [
                                                        'action' => 'new',
                                                    ],
                                                ],
                                            ],
                                            'view' => [
                                                'type'    => Segment::class,
                                                'options' => [
                                                    'route'    => '/view/[:id].html',
                                                    'defaults' => [
                                                        'action' => 'view',

                                                    ],
                                                ],
                                            ],
                                            'edit' => [
                                                'type'    => Segment::class,
                                                'options' => [
                                                    'route'    => '/edit/[:id].html',
                                                    'defaults' => [
                                                        'action' => 'edit',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'reviewer' => [
                                'type'          => 'Segment',
                                'options'       => [
                                    'route'    => '/reviewer',
                                    'defaults' => [
                                        'controller' => Controller\ReviewerManagerController::class,
                                        'action'     => 'list',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes'  => [
                                    'export'  => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/projects[/:projectId].txt',
                                            'defaults' => [
                                                'action' => 'export',
                                            ],
                                        ],
                                    ],
                                    'roster'  => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/roster.html',
                                            'defaults' => [
                                                'action' => 'roster',
                                            ],
                                        ],
                                    ],
                                    'list'    => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/list/project-[:projectId].html',
                                            'defaults' => [
                                                'action' => 'list',
                                            ],
                                        ],
                                    ],
                                    'new'     => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/new/project-[:projectId].html',
                                            'defaults' => [
                                                'action' => 'new',
                                            ],
                                        ],
                                    ],
                                    'edit'    => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/edit/[:id].html',
                                            'defaults' => [
                                                'action' => 'edit',
                                            ],
                                        ],
                                    ],
                                    'delete'  => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/delete/[:id].html',
                                            'defaults' => [
                                                'action' => 'delete',
                                            ],
                                        ],
                                    ],
                                    'contact' => [
                                        'type'          => 'Segment',
                                        'options'       => [
                                            'route'    => '/contact',
                                            'defaults' => [
                                                'controller' => Controller\Reviewer\ContactManagerController::class,
                                                'action'     => 'list',
                                            ],
                                        ],
                                        'may_terminate' => false,
                                        'child_routes'  => [
                                            'list' => [
                                                'type'    => 'Segment',
                                                'options' => [
                                                    'route'    => '/list[/f-:encodedFilter][/page-:page].html',
                                                    'defaults' => [
                                                        'action' => 'list',
                                                    ],
                                                ],
                                            ],
                                            'view' => [
                                                'type'    => 'Segment',
                                                'options' => [
                                                    'route'    => '/view/[:id].html',
                                                    'defaults' => [
                                                        'action' => 'view',
                                                    ],
                                                ],
                                            ],
                                            'edit' => [
                                                'type'    => 'Segment',
                                                'options' => [
                                                    'route'    => '/edit/[:id].html',
                                                    'defaults' => [
                                                        'action' => 'edit',
                                                    ],
                                                ],
                                            ],
                                            'new'  => [
                                                'type'    => 'Literal',
                                                'options' => [
                                                    'route'    => '/new.html',
                                                    'defaults' => [
                                                        'action' => 'new',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'feedback'   => [
                        'type'          => 'Segment',
                        'options'       => [
                            'route'    => '/feedback',
                            'defaults' => [
                                'controller' => Controller\FeedbackController::class,
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'new'  => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/new/version-[:version].html',
                                    'defaults' => [
                                        'action' => 'new',
                                    ],
                                ],
                            ],
                            'view' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/view/[:id].html',
                                    'defaults' => [
                                        'action' => 'view',

                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/edit/[:id].html',
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'project'    => [
                        'child_routes'  => [
                            'evaluation'       => [
                                'type'          => 'Segment',
                                'options'       => [
                                    'route'    => '/evaluation',
                                    'defaults' => [
                                        'action'     => 'list',
                                        'controller' => Controller\EvaluationController::class,
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes'  => [
                                    'edit' => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/edit/[:id].html',
                                            'defaults' => [
                                                'action' => 'edit',
                                            ],
                                        ],
                                    ],
                                    'new'  => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/new/project-[:project].html',
                                            'defaults' => [
                                                'action' => 'new',
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
