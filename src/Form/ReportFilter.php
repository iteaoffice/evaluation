<?php
/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @category    Project
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2017 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\Form;

use Doctrine\ORM\EntityManager;
use Program\Entity\Call\Call;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Entity\Report\Type as ReportType;
use Evaluation\Service\EvaluationReportService;
use Zend\Form\Element;
use Zend\Form\Fieldset;
use Zend\Form\Form;
use function array_combine;
use function array_reverse;
use function date;
use function range;
use function sprintf;

/**
 * Class ReportFilter
 * @package Evaluation\Form
 */
final class ReportFilter extends Form
{
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->setAttribute('method', 'get');
        $this->setAttribute('action', '');

        $filterFieldset = new Fieldset('filter');

        $filterFieldset->add([
            'type'       => Element\Text::class,
            'name'       => 'search',
            'attributes' => [
                'class'       => 'form-control',
                'placeholder' => _('txt-search'),
            ],
        ]);

        $filterFieldset->add([
            'type'       => Element\Select::class,
            'name'       => 'type',
            'options'    => [
                'inline'        => true,
                'label'         => _('txt-type'),
                'value_options' => [
                    EvaluationReport::TYPE_INDIVIDUAL => _('txt-individual'),
                    EvaluationReport::TYPE_FINAL      => _('txt-final'),
                ],
            ],
            'attributes' => [
                'value' => EvaluationReport::TYPE_INDIVIDUAL,
            ]
        ]);

        $filterFieldset->add([
            'type'    => Element\Select::class,
            'name'    => 'status',
            'options' => [
                'inline'        => true,
                'label'         => _('txt-status'),
                'value_options' => [
                    EvaluationReportService::STATUS_NEW         => _('txt-new'),
                    EvaluationReportService::STATUS_IN_PROGRESS => _('txt-in-progress'),
                    EvaluationReportService::STATUS_FINAL       => _('txt-final')
                ],
                'empty_option'  => _('txt-all'),
            ]
        ]);

        $types = [];
        /** @var ReportType $reportType */
        foreach ($entityManager->getRepository(ReportType::class)->findAll() as $reportType) {
            $types[$reportType->getId()] = $reportType->getType();
        }
        $filterFieldset->add([
            'type'    => Element\Select::class,
            'name'    => 'subject',
            'options' => [
                'inline'        => true,
                'label'         => _('txt-subject'),
                'value_options' => $types,
                'empty_option'  => _('txt-all-project-versions'),
            ],
        ]);

        $range = array_reverse(range(2009, (int)date('Y')));
        $filterFieldset->add([
            'type'    => Element\Select::class,
            'name'    => 'year',
            'options' => [
                'inline'        => true,
                'label'         => _('txt-year'),
                'value_options' => array_combine($range, $range),
                'empty_option'  => _('txt-all'),
            ],
        ]);

        $filterFieldset->add([
            'type'    => Element\Select::class,
            'name'    => 'period',
            'options' => [
                'inline'        => true,
                'label'         => _('txt-period'),
                'value_options' => [
                    1 => sprintf(_('txt-semester-%d'), 1),
                    2 => sprintf(_('txt-semester-%d'), 2)
                ],
                'empty_option'  => _('txt-all'),
            ],
        ]);

        $calls = [];
        /** @var \Program\Repository\Call\Call $repository */
        $repository = $entityManager->getRepository(Call::class);
        /** @var Call $call */
        foreach ($repository->findFiltered(['order' => 'id'])->getQuery()->getResult() as $call) {
            $calls[$call->getId()] = (string)$call;
        }

        $filterFieldset->add([
            'type'    => Element\Select::class,
            'name'    => 'call',
            'options' => [
                'inline'        => true,
                'label'         => _('txt-program-call'),
                'value_options' => $calls,
                'empty_option'  => _('txt-all'),
            ],
        ]);

        $this->add($filterFieldset);

        $this->add([
            'type'       => Element\Submit::class,
            'name'       => 'submit',
            'attributes' => [
                'id'    => 'submit',
                'class' => 'btn btn-primary',
                'value' => _('txt-filter'),
            ],
        ]);

        $this->add([
            'type'       => Element\Submit::class,
            'name'       => 'presentation',
            'attributes' => [
                'id'    => 'submit',
                'class' => 'btn btn-primary',
                'value' => _('txt-download-presentation'),
            ],
        ]);

        $this->add([
            'type'       => Element\Submit::class,
            'name'       => 'clear',
            'attributes' => [
                'id'    => 'cancel',
                'class' => 'btn btn-warning',
                'value' => _('txt-cancel'),
            ],
        ]);
    }
}
