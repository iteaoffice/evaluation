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

use Calendar\Entity\ContactRole;
use Contact\Form\Element\Contact as ContactFormElement;
use DateTime;
use Doctrine\ORM\EntityManager;
use Evaluation\Form\ReviewRoster;
use Evaluation\Service\EvaluationService;
use Evaluation\Service\FormService;
use Evaluation\Service\ReviewerService;
use Evaluation\Controller\Plugin\RosterGenerator;
use Evaluation\Entity\Reviewer\Contact;
use Evaluation\Entity\Reviewer;
use Evaluation\Entity\Reviewer\Type as ReviewerType;
use Project\Service\ProjectService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;

use function array_merge_recursive;
use function in_array;

/**
 * @method FlashMessenger flashMessenger()
 */
final class ReviewScheduleController extends AbstractActionController
{
    private EvaluationService $evaluationService;
    private TranslatorInterface $translator;

    public function __construct(EvaluationService $evaluationService, TranslatorInterface $translator)
    {
        $this->evaluationService = $evaluationService;
        $this->translator = $translator;
    }

    public function overviewAction(): ViewModel
    {
        return new ViewModel([
            'schedule' => $this->evaluationService->getReviewSchedule(),
        ]);
    }

    public function newAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $data    = $request->getPost()->toArray();
        $project = $this->projectService->findProjectById((int)$this->params()->fromRoute('projectId'));

        if (null === $project) {
            return $this->notFoundAction();
        }

        $form = $this->formService->prepare(new Reviewer(), $data);
        $form->remove('delete');

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/reviewer/list',
                    ['projectId' => $project->getId()]
                );
            }

            if ($form->isValid()) {
                /** @var Reviewer $projectReview */
                $projectReview = $form->getData();
                $projectReview->setProject($project);

                $projectReview = $this->reviewerService->save($projectReview);
                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('txt-project-reviewer-%s-has-been-successfully-added'),
                        $projectReview->getContact()->parseFullName()
                    )
                );

                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/reviewer/list',
                    ['projectId' => $projectReview->getProject()->getId()]
                );
            }
        }

        return new ViewModel([
            'form'    => $form,
            'project' => $project,
        ]);
    }

    public function editAction()
    {
        /** @var Request $request */
        $request        = $this->getRequest();
        /** @var Reviewer $projectReview */
        $projectReview  = $this->reviewerService->find(Reviewer::class, (int)$this->params('id'));
        $project        = $projectReview->getProject();
        $data           = $request->getPost()->toArray();
        $form           = $this->formService->prepare($projectReview, $data);
        /** @var ContactFormElement $contactElement */
        $contactElement = $form->get($projectReview->get('underscore_entity_name'))->get('contact');
        $contactElement->setValueOptions(
            [$projectReview->getContact()->getId() => $projectReview->getContact()->getDisplayName()]
        )->setDisableInArrayValidator(true);

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/reviewer/list',
                    ['projectId' => $project->getId(),]
                );
            }

            if (isset($data['delete'])) {
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/reviewer/delete',
                    ['id' => $projectReview->getId(),]
                );
            }

            if ($form->isValid()) {
                /** @var Reviewer $projectReview */
                $projectReview = $form->getData();
                $this->reviewerService->save($projectReview);
                $this->flashMessenger()->addSuccessMessage(sprintf(
                    $this->translator->translate('txt-project-reviewer-%s-has-been-successfully-modified'),
                    $projectReview->getContact()->parseFullName()
                ));

                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/reviewer/list',
                    ['projectId' => $project->getId(),]
                );
            }
        }

        return new ViewModel([
            'form'    => $form,
            'project' => $project,
        ]);
    }

    public function deleteAction(): Response
    {
        /** @var Reviewer $projectReviewer */
        $projectReviewer = $this->reviewerService->find(Reviewer::class, (int)$this->params('id'));
        $this->reviewerService->delete($projectReviewer);
        $this->flashMessenger()->addSuccessMessage(sprintf(
            $this->translator->translate('txt-project-reviewer-%s-has-been-successfully-removed-from-this-project'),
            $projectReviewer->getContact()->parseFullName()
        ));

        return $this->redirect()->toRoute(
            'zfcadmin/evaluation/reviewer/list',
            ['projectId' => $projectReviewer->getProject()->getId(),]
        );
    }
}
