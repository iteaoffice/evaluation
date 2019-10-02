<?php
/**
*
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2019 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\Controller;

use Evaluation\Entity\Feedback;
use Evaluation\Service\EvaluationService;
use Evaluation\Service\FormService;
use Project\Controller\Plugin\GetFilter;
use Project\Entity\Version\Version;
use Project\Service\VersionService;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Zend\View\Model\ViewModel;
use function array_merge_recursive;
use function sprintf;

/**
 * @package Project\Controller
 * @method GetFilter getProjectFilter()
 * @method FlashMessenger flashMessenger()
 */
final class FeedbackController extends AbstractActionController
{
    /**
     * @var EvaluationService
     */
    private $evaluationService;
    /**
     * @var VersionService
     */
    private $versionService;
    /**
     * @var FormService
     */
    private $formService;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        EvaluationService $evaluationService,
        VersionService $versionService,
        FormService $formService,
        TranslatorInterface $translator
    ) {
        $this->evaluationService = $evaluationService;
        $this->versionService = $versionService;
        $this->formService = $formService;
        $this->translator = $translator;
    }

    public function newAction()
    {
        $data = array_merge(
            [
                'evaluation_entity_feedback' => ['version' => $this->params('version')],
            ],
            $this->getRequest()->getPost()->toArray(),
            $this->getRequest()->getFiles()->toArray()
        );

        $form = $this->formService->prepare(new Feedback(), $data);

        $form->get('evaluation_entity_feedback')->get('version')->getProxy()->setLabelGenerator(
            static function (
                Version $version
            ) {
                return sprintf('%s (%s)', $version->getProject(), $version->getVersionType());
            }
        );

        if ($this->getRequest()->isPost() && $form->isValid()) {
            /** @var Feedback $feedback */
            $feedback = $form->getData();

            $this->evaluationService->save($feedback);

            return $this->redirect()
                ->toRoute(
                    'zfcadmin/feedback/view',
                    ['id' => $feedback->getId()]
                );
        }

        return new ViewModel(['form' => $form]);
    }

    public function editAction()
    {
        /** @var Feedback $feedback */
        $feedback = $this->evaluationService->find(Feedback::class, (int)$this->params('id'));

        $data = array_merge_recursive(
            $this->getRequest()->getPost()->toArray(),
            $this->getRequest()->getFiles()->toArray()
        );
        $form = $this->formService->prepare($feedback, $data);

        $form->get($feedback->get('underscore_entity_name'))->get('version')->getProxy()->setLabelGenerator(
            static function (
                Version $version
            ) {
                return sprintf('%s (%s)', $version->getProject(), $version->getVersionType());
            }
        );

        if ($this->getRequest()->isPost() && $form->isValid()) {
            if (isset($data['delete'])) {
                $this->evaluationService->delete($feedback);
                $this->flashMessenger()->addSuccessMessage(
                    sprintf($this->translator->translate('txt-feedback-has-successfully-been-deleted'))
                );

                return $this->redirect()
                    ->toRoute(
                        'zfcadmin/project/project/view',
                        ['id' => $feedback->getVersion()->getProject()->getId()],
                        ['fragment' => 'evaluation']
                    );
            }

            if (isset($data['cancel'])) {
                $this->flashMessenger()->addSuccessMessage(
                    sprintf($this->translator->translate('txt-editing-feedback-has-successfully-been-cancelled'))
                );

                return $this->redirect()
                    ->toRoute(
                        'zfcadmin/feedback/view',
                        ['id' => $feedback->getId()]
                    );
            }

            $feedback = $form->getData();

            $this->evaluationService->save($feedback);

            return $this->redirect()
                ->toRoute(
                    'zfcadmin/feedback/view',
                    ['id' => $feedback->getId()]
                );
        }

        return new ViewModel(['form' => $form]);
    }

    public function viewAction(): ViewModel
    {
        $feedback = $this->evaluationService->find(Feedback::class, (int)$this->params('id'));

        return new ViewModel(
            [
                'feedback'          => $feedback,
                'evaluationService' => $this->evaluationService,
                'versionService'    => $this->versionService,
            ]
        );
    }
}
