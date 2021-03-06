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
 * @method RosterGenerator rosterGenerator(string $type, string $configFile, int $reviewersPerProject, bool $includeSpareReviewers = false, bool $onlineReview = false, ?int $forceProjectsPerRound = null)
 */
final class ReviewerManagerController extends AbstractActionController
{
    private ReviewerService $reviewerService;
    private ProjectService $projectService;
    private FormService $formService;
    private EntityManager $entityManager;
    private TranslatorInterface $translator;

    public function __construct(
        ReviewerService $reviewerService,
        ProjectService $projectService,
        FormService $formService,
        EntityManager $entityManager,
        TranslatorInterface $translator
    ) {
        $this->reviewerService = $reviewerService;
        $this->projectService  = $projectService;
        $this->formService     = $formService;
        $this->entityManager   = $entityManager;
        $this->translator      = $translator;
    }

    public function listAction(): ViewModel
    {
        $project = $this->projectService->findProjectById((int)$this->params()->fromRoute('projectId'));

        if (null === $project) {
            return $this->notFoundAction();
        }

        $tomorrow               = new DateTime('tomorrow');
        $calendarItem           = null;
        $projectFutureReviewers = [];
        foreach ($project->getProjectCalendar() as $projectCalendar) {
            if ($projectCalendar->getCalendar()->getDateFrom() >= $tomorrow) {
                $calendarItem = $projectCalendar->getCalendar();
                /** @var \Calendar\Entity\Contact $attendee */
                foreach ($projectCalendar->getCalendar()->getCalendarContact() as $attendee) {
                    // Include steering group reviewers and spare reviewers
                    $stgRoles = [ContactRole::ROLE_STG_REVIEWER, ContactRole::ROLE_STG_SPARE_REVIEWER];
                    if (in_array($attendee->getRole()->getId(), $stgRoles, true)) {
                        $projectFutureReviewers[] = $attendee->getContact()->getProjectReviewerContact();
                    }
                }
                // Just list the reviewers for the first future calendar item
                break;
            }
        }

        $preferredReviewerType = $this->reviewerService->find(ReviewerType::class, ReviewerType::TYPE_PREFERRED);
        $ignoredReviewerType = $this->reviewerService->find(ReviewerType::class, ReviewerType::TYPE_IGNORED);

        return new ViewModel([
            'preferredReviewersManual' => $this->entityManager->getRepository(Reviewer::class)
                ->findBy(['project' => $project, 'type' => $preferredReviewerType]),
            'ignoredReviewersManual' => $this->entityManager->getRepository(Reviewer::class)
                ->findBy(['project' => $project, 'type' => $ignoredReviewerType]),
            'projectIgnoredReviewers'   => $this->entityManager->getRepository(Contact::class)
                ->findIgnoredReviewers($project),
            'projectFutureReviewers'    => $projectFutureReviewers,
            'calendarItem'              => $calendarItem,
            'project'                   => $project,
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

    public function rosterAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $form    = new ReviewRoster();

        if ($request->isPost()) {
            $data = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());
            $form->setData($data);

            if ($form->isValid()) {
                $excelFile = $form->get('excel')->getValue();
                if (! empty($excelFile['name']) && ($excelFile['error'] === 0)) {
                    $rosterGenerator = $this->rosterGenerator(
                        $form->get('type')->getValue(),
                        $excelFile['tmp_name'],
                        (int) $form->get('nr')->getValue(),
                        (bool) $form->get('include-spare')->getValue(),
                        (bool) $form->get('online')->getValue(),
                        (empty($form->get('projects')->getValue()) ? null : (int)$form->get('projects')->getValue())
                    );

                    return $rosterGenerator->parseResponse();
                }
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }
}
