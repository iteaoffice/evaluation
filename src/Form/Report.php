<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Form;

use Doctrine\Laminas\Hydrator\DoctrineObject as DoctrineHydrator;
use Doctrine\ORM\EntityManager;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Entity\Report\Criterion;
use Evaluation\Entity\Report\Result;
use Evaluation\InputFilter\Report\ResultFilter;
use Evaluation\Service\EvaluationReportService;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;
use Laminas\InputFilter\CollectionInputFilter;
use Laminas\InputFilter\InputFilter;

/**
 * Class Report
 * @package Evaluation\Form
 */
final class Report extends Form
{
    public function __construct(EvaluationReport $report, EvaluationReportService $reportService, EntityManager $entityManager)
    {
        parent::__construct($report->get('underscore_entity_name'));
        $this->setAttributes(
            [
                'method' => 'post',
                'role'   => 'form',
                'class'  => 'form-horizontal',
                'action' => ''
            ]
        );
        $this->setUseAsBaseFieldset(true);
        $doctrineHydrator = new DoctrineHydrator($entityManager);
        $this->setHydrator($doctrineHydrator);
        $this->bind($report);

        // Setup input filters
        $resultsFilter = new CollectionInputFilter();
        $resultsFilter->setInputFilter(new ResultFilter());
        $reportFilter = new InputFilter();
        $reportFilter->add(
            [
                'name'     => 'score',
                'required' => false,
            ]
        );
        $reportFilter->add($resultsFilter, 'report-result');
        $this->setInputFilter($reportFilter);

        $resultCollection = new Element\Collection('report-result');
        $resultCollection->setCreateNewObjects(false);
        $resultCollection->setAllowAdd(false);
        $resultCollection->setAllowRemove(false);
        /** @var Result $reportResult */
        foreach ($reportService->getSortedResults($report) as $reportResult) {
            /** @var Criterion $criterion */
            $criterion            = $reportResult->getCriterionVersion()->getCriterion();
            $hasScore             = $criterion->getHasScore();
            $reportResultFieldset = new Fieldset($criterion->getId());
            $reportResultFieldset->setHydrator($doctrineHydrator);
            $reportResultFieldset->setObject($reportResult);
            $reportResultFieldset->setAllowedObjectBindingClass(get_class($reportResult));

            if ($hasScore) {
                $reportResultFieldset->add(
                    [
                        'type'       => Element\Select::class,
                        'name'       => 'score',
                        'attributes' => [
                            'value' => $reportResult->getScore()
                        ],
                        'options'    => [
                            'label'         => $criterion->getCriterion(),
                            'help-block'    => nl2br((string)$criterion->getHelpBlock()),
                            'value_options' => EvaluationReport\Result::getScoreValues()
                        ],
                    ]
                );
            }

            $optionTemplate = [
                'label'      => ($hasScore ? ' ' : $criterion->getCriterion()),
                'help-block' => ($hasScore ? ' ' : nl2br((string)$criterion->getHelpBlock())),
            ];

            switch ($criterion->getInputType()) {
                case Criterion::INPUT_TYPE_BOOL:
                    $reportResultFieldset->add(
                        [
                            'type'       => Element\Radio::class,
                            'name'       => 'value',
                            'attributes' => [
                                'value' => $reportResult->getValue()
                            ],
                            'options'    => array_merge(
                                $optionTemplate,
                                [
                                    'value_options' => [
                                        'Yes' => _('txt-yes'),
                                        'No'  => _('txt-no'),
                                    ],
                                ]
                            ),
                        ]
                    );
                    break;

                case Criterion::INPUT_TYPE_TEXT:
                    $attributes          = ($hasScore ? ['placeholder' => 'txt-comments'] : []);
                    $attributes['value'] = ($hasScore ? $reportResult->getComment() : $reportResult->getValue());
                    $reportResultFieldset->add(
                        [
                            'type'       => Element\Textarea::class,
                            'name'       => ($hasScore ? 'comment' : 'value'),
                            'attributes' => $attributes,
                            'options'    => $optionTemplate,
                        ]
                    );
                    break;

                case Criterion::INPUT_TYPE_SELECT:
                    $reportResultFieldset->add(
                        [
                            'type'       => Element\Select::class,
                            'name'       => 'value',
                            'attributes' => [
                                'value' => ($hasScore ? $reportResult->getComment() : $reportResult->getValue())
                            ],
                            'options'    => array_merge(
                                $optionTemplate,
                                ['value_options' => json_decode($criterion->getValues(), true)]
                            ),
                        ]
                    );
                    break;

                case Criterion::INPUT_TYPE_STRING:
                default:
                    $reportResultFieldset->add([
                        'type'       => Element\Text::class,
                        'name'       => ($hasScore ? 'comment' : 'value'),
                        'attributes' => [
                            'value' => ($hasScore ? $reportResult->getComment() : $reportResult->getValue())
                        ],
                        'options'    => $optionTemplate
                    ]);
            }
            // Add result to the result collection
            $resultCollection->add($reportResultFieldset);
        }

        // Add the result collection to the form
        $this->add($resultCollection);

        $scores           = ($report->getProjectReportReport() === null)
            ? $report::getVersionScores() : $report::getReportScores();
        $translatedScores = array_map(
            function ($scoreLabel) {
                return _($scoreLabel);
            },
            $scores
        );
        $this->add(
            [
                'type'       => Element\Select::class,
                'name'       => 'score',
                'options'    => [
                    'label'         => _('txt-score'),
                    'help-block'    => _('txt-evaluation-report-score-help-block'),
                    'empty_option'  => _('txt-select-a-score'),
                    'value_options' => $translatedScores,
                ],
                'attributes' => [
                    'value' => $report->getScore()
                ],
            ]
        );

        $this->add(
            [
                'type'       => Element\File::class,
                'name'       => 'excel',
                'options'    => [
                    'label'      => _('txt-file'),
                    'help-block' => _('txt-select-a-filled-in-evaluation-report-excel')
                ],
                'attributes' => [
                    'accept' => '.xlsx',
                ],
            ]
        );

        $this->add(
            [
                'type'       => Element\Submit::class,
                'name'       => 'upload',
                'attributes' => [
                    'class' => 'btn btn-primary',
                    'value' => _('txt-upload'),
                ],
            ]
        );

        $this->add(
            [
                'type'       => Element\Submit::class,
                'name'       => 'cancel',
                'attributes' => [
                    'class' => 'btn btn-warning',
                    'value' => _('txt-cancel'),
                ],
            ]
        );

        $this->add(
            [
                'type'       => Element\Submit::class,
                'name'       => 'submit',
                'attributes' => [
                    'class' => 'btn btn-primary',
                    'value' => _('txt-submit'),
                ],
            ]
        );
    }
}
