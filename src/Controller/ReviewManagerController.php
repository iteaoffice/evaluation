<?php

declare(strict_types=1);

namespace Evaluation\Controller;

use Calendar\Entity\ContactRole;
use Contact\Form\Element\Contact as ContactFormElement;
use DateTime;
use Doctrine\ORM\EntityManager;
use Evaluation\Form\ReviewRoster;
use Evaluation\Service\FormService;
use Evaluation\Service\ReviewService;
use Project\Controller\Plugin\Evaluation\RosterGenerator;
use Project\Entity\Review\Contact;
use Project\Entity\Review\Review;
use Project\Entity\Review\Type as ReviewType;
use Project\Repository\Review\Contact as ContactRepository;
use Project\Service\ProjectService;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Zend\View\Model\ViewModel;
use function array_merge_recursive;
use function in_array;
use function unlink;

/**
 * Class ReviewManagerController
 * @package Evaluation\Controller
 * @method FlashMessenger flashMessenger()
 * @method RosterGenerator rosterGenerator(string $type, string $configFile, int $reviewersPerProject, bool $includeSpareReviewers = false, ?int $forceProjectsPerRound = null)
 */
final class ReviewManagerController extends AbstractActionController
{
    /**
     * @var ReviewService
     */
    private $reviewService;
    /**
     * @var ProjectService
     */
    private $projectService;
    /**
     * @var FormService
     */
    private $formService;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        ReviewService       $reviewService,
        ProjectService      $projectService,
        FormService         $formService,
        EntityManager       $entityManager,
        TranslatorInterface $translator
    ) {
        $this->reviewService  = $reviewService;
        $this->projectService = $projectService;
        $this->formService    = $formService;
        $this->entityManager  = $entityManager;
        $this->translator     = $translator;
    }

    public function listAction(): ViewModel
    {
        $projectReviewType = $this->reviewService->find(ReviewType::class, ReviewType::TYPE_PREFERRED);
        $project = $this->projectService->findProjectById((int)$this->params()->fromRoute('projectId'));

        if (null === $project) {
            return $this->notFoundAction();
        }

        $projectPreferredReviewers = $this->entityManager->getRepository(Review::class)->findBy(
            ['project' => $project, 'type' => $projectReviewType]
        );

        /** @var ContactRepository $reviewContactRepository */
        $reviewContactRepository = $this->entityManager->getRepository(Contact::class);

        $tomorrow = new DateTime('tomorrow');
        $calendarItem = null;
        $projectFutureReviewers = [];
        foreach ($project->getProjectCalendar() as $projectCalendar) {
            if ($projectCalendar->getCalendar()->getDateFrom() >= $tomorrow) {
                $calendarItem = $projectCalendar->getCalendar();
                /** @var \Calendar\Entity\Contact $attendee */
                foreach ($projectCalendar->getCalendar()->getCalendarContact() as $attendee) {
                    // Include steering group reviewers and spare reviewers
                    $stgRoles = [ContactRole::ROLE_STG_REVIEWER, ContactRole::ROLE_STG_SPARE_REVIEWER];
                    if (in_array($attendee->getRole()->getId(), $stgRoles, true)) {
                        $projectFutureReviewers[] = $attendee->getContact()->getProjectReviewContact();
                    }
                }
                // Just list the reviewers for the first future calendar item
                break;
            }
        }

        return new ViewModel([
            'projectPreferredReviewers' => $projectPreferredReviewers,
            'projectIgnoredReviewers'   => $reviewContactRepository->findIgnoredReviewers($project),
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

        $form = $this->formService->prepare(new Review(), $data);
        $form->remove('delete');

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute(
                    'zfcadmin/project/review/list',
                    ['projectId' => $project->getId()]
                );
            }

            if ($form->isValid()) {
                /** @var Review $projectReview */
                $projectReview = $form->getData();
                $projectReview->setProject($project);

                $projectReview = $this->projectService->save($projectReview);
                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('txt-project-reviewer-%s-has-been-successfully-added'),
                        $projectReview->getContact()->parseFullName()
                    )
                );

                return $this->redirect()->toRoute(
                    'zfcadmin/project/review/list',
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
        /** @var Review $projectReview */
        $projectReview  = $this->projectService->find(Review::class, (int)$this->params('id'));
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
                    'zfcadmin/project/review/list',
                    ['projectId' => $project->getId(),]
                );
            }

            if (isset($data['delete'])) {
                return $this->redirect()->toRoute(
                    'zfcadmin/project/review/delete',
                    ['id' => $projectReview->getId(),]
                );
            }

            if ($form->isValid()) {
                /** @var Review $projectReview */
                $projectReview = $form->getData();
                $this->projectService->save($projectReview);
                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('txt-project-reviewer-%s-has-been-successfully-modified'),
                        $projectReview->getContact()->parseFullName()
                    )
                );

                return $this->redirect()->toRoute(
                    'zfcadmin/project/review/list',
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
        /** @var Review $projectReview */
        $projectReview = $this->projectService->find(Review::class, (int)$this->params('id'));
        $this->projectService->delete($projectReview);
        $this->flashMessenger()->addSuccessMessage(
            sprintf(
                $this->translator->translate('txt-project-reviewer-%s-has-been-successfully-removed-from-this-project'),
                $projectReview->getContact()->parseFullName()
            )
        );

        return $this->redirect()->toRoute(
            'zfcadmin/project/review/list',
            ['projectId' => $projectReview->getProject()->getId(),]
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
                if (!empty($excelFile['name']) && ($excelFile['error'] === 0)) {
                    $rosterGenerator = $this->rosterGenerator(
                        $form->get('type')->getValue(),
                        $excelFile['tmp_name'],
                        (int) $form->get('nr')->getValue(),
                        (bool) $form->get('include-spare')->getValue(),
                        (empty($form->get('projects')->getValue()) ? null : (int)$form->get('projects')->getValue())
                    );
                    unlink($excelFile['tmp_name']);
                    return $rosterGenerator->parseResponse();
                }
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }
}
