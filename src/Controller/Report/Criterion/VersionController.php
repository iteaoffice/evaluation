<?php
/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @topic       Project
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2019 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\Controller\Report\Criterion;

use DoctrineORMModule\Form\Element\EntitySelect;
use Evaluation\Entity\Report\Criterion\Topic;
use Evaluation\Entity\Report\Criterion\Version as CriterionVersion;
use Evaluation\Entity\Report\Version as ReportVersion;
use Evaluation\Service\EvaluationReportService;
use Evaluation\Service\FormService;
use Zend\Form\Fieldset;
use Zend\Http\Request;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Zend\View\Model\ViewModel;
use function in_array;

/**
 * Class VersionController
 * @method FlashMessenger flashMessenger()
 * @package Evaluation\Controller\Report\Criterion
 */
final class VersionController extends AbstractActionController
{
    /**
     * @var EvaluationReportService
     */
    private $evaluationReportService;

    /**
     * @var FormService
     */
    private $formService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        EvaluationReportService $evaluationReportService,
        FormService             $formService,
        TranslatorInterface     $translator
    ) {
        $this->evaluationReportService = $evaluationReportService;
        $this->formService             = $formService;
        $this->translator              = $translator;
    }

    public function viewAction(): ViewModel
    {
        $criterionVersion = $this->evaluationReportService->find(
            CriterionVersion::class,
            (int) $this->params('id')
        );

        if ($criterionVersion === null) {
            return $this->notFoundAction();
        }

        return new ViewModel([
            'criterionVersion' => $criterionVersion
        ]);
    }

    public function addAction()
    {
        /** @var Request $request */
        $request       = $this->getRequest();
        /** @var ReportVersion $reportVersion */
        $reportVersion = $this->evaluationReportService->find(
            ReportVersion::class,
            (int) $this->params('reportVersionId')
        );
        $data             = $request->getPost()->toArray();
        $criterionVersion = new CriterionVersion();
        $criterionVersion->setReportVersion($reportVersion);
        $form             = $this->formService->prepare($criterionVersion, $data);
        $form->remove('delete');

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report/version/view',
                    ['id' => $reportVersion->getId()]
                );
            }

            if ($form->isValid()) {
                /** @var CriterionVersion $criterionVersion */
                $criterionVersion = $form->getData();
                $this->evaluationReportService->save($criterionVersion);
                $this->flashMessenger()->addSuccessMessage(
                    $this->translator->translate('txt-evaluation-report-criterion-version-has-successfully-been-saved')
                );
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report/version/view',
                    ['id' => $reportVersion->getId()]
                );
            }
        }

        return new ViewModel([
            'form'          => $form,
            'reportVersion' => $reportVersion
        ]);
    }

    public function editAction()
    {
        /** @var Request $request */
        $request          = $this->getRequest();
        /** @var CriterionVersion $criterionVersion */
        $criterionVersion = $this->evaluationReportService->find(
            CriterionVersion::class,
            (int) $this->params('id')
        );

        if ($criterionVersion === null) {
            return $this->notFoundAction();
        }

        $data       = $request->getPost()->toArray();
        $form       = $this->formService->prepare($criterionVersion, $data);
        // Unfortunately this needs to happen in the controller as during form creation (VersionFieldset.php) the
        // collection is still empty
        $allowedValueOptions = [];
        /** @var Topic $topic */
        foreach ($criterionVersion->getReportVersion()->getTopics() as $topic) {
            $allowedValueOptions[] = $topic->getId();
        }
        $fieldsets = $form->get('evaluation_entity_report_criterion_version')->get('versionTopics')->getFieldsets();
        /** @var Fieldset $fieldset */
        foreach ($fieldsets as $fieldset) {
            /** @var EntitySelect $topic */
            $topic = $fieldset->get('topic');
            $valueOptions = $topic->getValueOptions();
            foreach ($valueOptions as $key => $option) {
                if (!in_array($option['value'], $allowedValueOptions)
                    && ($fieldset->get('topic')->getValue() !== $option['value'])) {
                    unset($valueOptions[$key]);
                }
            }
            $topic->setValueOptions($valueOptions);
        }

        $hasResults = false;
        if ($hasResults) {
            $form->remove('delete');
        }

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report/version/view',
                    ['id' => $criterionVersion->getReportVersion()->getId()]
                );
            }

            if (isset($data['delete']) && !$hasResults) {
                $this->evaluationReportService->delete($criterionVersion);
                $this->flashMessenger()->addSuccessMessage(
                    $this->translator->translate('txt-evaluation-report-criterion-version-has-successfully-been-deleted')
                );
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report/version/view',
                    ['id' => $criterionVersion->getReportVersion()->getId()]
                );
            }

            if ($form->isValid()) {
                /** @var CriterionVersion $criterionVersion */
                $criterionVersion = $form->getData();
                $this->evaluationReportService->save($criterionVersion);
                $this->flashMessenger()->addSuccessMessage(
                    $this->translator->translate('txt-evaluation-report-criterion-version-has-successfully-been-saved')
                );
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report/version/view',
                    ['id' => $criterionVersion->getReportVersion()->getId()]
                );
            }
        }

        return new ViewModel([
            'form'             => $form,
            'criterionVersion' => $criterionVersion,
            'reportVersion'    => $criterionVersion->getReportVersion()
        ]);
    }
}
