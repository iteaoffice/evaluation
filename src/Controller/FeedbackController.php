<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Controller;

use Evaluation\Entity\Feedback;
use Evaluation\Service\EvaluationService;
use Evaluation\Service\FormService;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;
use Project\Controller\Plugin\GetFilter;
use Project\Service\VersionService;

/**
 * @method GetFilter getProjectFilter()
 * @method FlashMessenger flashMessenger()
 */
final class FeedbackController extends AbstractActionController
{
    private EvaluationService $evaluationService;
    private VersionService $versionService;
    private FormService $formService;
    private TranslatorInterface $translator;

    public function __construct(
        EvaluationService $evaluationService,
        VersionService $versionService,
        FormService $formService,
        TranslatorInterface $translator
    ) {
        $this->evaluationService = $evaluationService;
        $this->versionService    = $versionService;
        $this->formService       = $formService;
        $this->translator        = $translator;
    }

    public function newAction()
    {
        $version = $this->versionService->findVersionById((int)$this->params('version'));

        if (null === $version) {
            return $this->notFoundAction();
        }

        $feedback = (new Feedback())->setVersion($version);
        $data     = $this->getRequest()->getPost()->toArray();

        $form = $this->formService->prepare($feedback, $data);

        if ($this->getRequest()->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()
                    ->toRoute(
                        'zfcadmin/project/version/feedback',
                        ['id' => $version->getId()]
                    );
            }

            if ($form->isValid()) {
                /** @var Feedback $feedback */
                $feedback = $form->getData();

                $this->flashMessenger()->addSuccessMessage(
                    $this->translator->translate('txt-consortium-feedback-has-been-saved-successfully')
                );

                $this->evaluationService->save($feedback);

                return $this->redirect()
                    ->toRoute(
                        'zfcadmin/feedback/view',
                        ['id' => $feedback->getId()]
                    );
            }
        }

        return new ViewModel([
            'form'           => $form,
            'version'        => $version,
            'versionService' => $this->versionService
        ]);
    }

    public function editAction()
    {
        /** @var Feedback $feedback */
        $feedback = $this->evaluationService->find(Feedback::class, (int)$this->params('id'));

        $data = $this->getRequest()->getPost()->toArray();
        $form = $this->formService->prepare($feedback, $data);

        if ($this->getRequest()->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()
                    ->toRoute(
                        'zfcadmin/project/version/feedback',
                        ['id' => $feedback->getId()]
                    );
            }

            if (isset($data['delete'])) {
                $this->evaluationService->delete($feedback);

                $this->flashMessenger()->addSuccessMessage(
                    $this->translator->translate('txt-consortium-feedback-has-successfully-been-deleted')
                );

                return $this->redirect()
                    ->toRoute(
                        'zfcadmin/project/project/public-authority-evaluation',
                        ['id' => $feedback->getVersion()->getProject()->getId()]
                    );
            }


            if ($form->isValid()) {
                /** @var Feedback $feedback */
                $feedback = $form->getData();

                $this->flashMessenger()->addSuccessMessage(
                    $this->translator->translate('txt-consortium-feedback-has-updated-successfully')
                );

                $this->evaluationService->save($feedback);

                return $this->redirect()
                    ->toRoute(
                        'zfcadmin/project/version/feedback',
                        ['id' => $feedback->getId()]
                    );
            } else {
                var_dump($form->getInputFilter()->getMessages());
            }
        }

        return new ViewModel([
            'form'           => $form,
            'version'        => $feedback->getVersion(),
            'versionService' => $this->versionService
        ]);
    }
}
