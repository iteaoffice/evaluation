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

namespace Project\Controller;

use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as PaginatorAdapter;
use Project\Entity\Evaluation\Feedback;
use Project\Entity\Version\Version;
use Project\Form\FeedbackFilter;
use Project\Service\FormService;
use Project\Service\ProjectService;
use Project\Service\VersionService;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;

/**
 * Class FeedbackController
 *
 * @package Project\Controller
 */
final class FeedbackController extends ProjectAbstractController
{
    /**
     * @var ProjectService
     */
    private $projectService;
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
        ProjectService $projectService,
        VersionService $versionService,
        FormService $formService,
        TranslatorInterface $translator
    ) {
        $this->projectService = $projectService;
        $this->versionService = $versionService;
        $this->formService = $formService;
        $this->translator = $translator;
    }


    public function listAction(): ViewModel
    {
        $page = $this->params()->fromRoute('page', 1);
        $filterPlugin = $this->getProjectFilter();
        $contactQuery = $this->projectService->findFiltered(Feedback::class, $filterPlugin->getFilter());

        $paginator
            = new Paginator(new PaginatorAdapter(new ORMPaginator($contactQuery, false)));
        $paginator::setDefaultItemCountPerPage(($page === 'all') ? PHP_INT_MAX : 20);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange(ceil($paginator->getTotalItemCount() / $paginator::getDefaultItemCountPerPage()));

        $form = new FeedbackFilter();

        return new ViewModel(
            [
                'form'          => $form,
                'paginator'     => $paginator,
                'encodedFilter' => \urlencode($filterPlugin->getHash()),
                'order'         => $filterPlugin->getOrder(),
                'direction'     => $filterPlugin->getDirection(),
            ]
        );
    }

    public function newAction()
    {
        $data = array_merge(
            [
                'project_entity_evaluation_feedback' => ['version' => $this->params('version')],
            ],
            $this->getRequest()->getPost()->toArray(),
            $this->getRequest()->getFiles()->toArray()
        );

        $form = $this->formService->prepare(new Feedback(), $data);

        $form->get('project_entity_evaluation_feedback')->get('version')->getProxy()->setLabelGenerator(
            function (
                Version $version
            ) {
                return \sprintf('%s (%s)', $version->getProject(), $version->getVersionType());
            }
        );

        if ($this->getRequest()->isPost() && $form->isValid()) {
            /** @var Feedback $feedback */
            $feedback = $form->getData();

            $this->projectService->save($feedback);

            return $this->redirect()
                ->toRoute(
                    'zfcadmin/project/project/view',
                    ['id' => $feedback->getVersion()->getProject()->getId()],
                    ['fragment' => 'evaluation']
                );
        }

        return new ViewModel(['form' => $form]);
    }

    public function editAction()
    {
        /** @var Feedback $feedback */
        $feedback = $this->projectService->find(Feedback::class, (int)$this->params('id'));

        $data = \array_merge_recursive(
            $this->getRequest()->getPost()->toArray(),
            $this->getRequest()->getFiles()->toArray()
        );
        $form = $this->formService->prepare($feedback, $data);

        $form->get($feedback->get('underscore_entity_name'))->get('version')->getProxy()->setLabelGenerator(
            function (
                Version $version
            ) {
                return sprintf('%s (%s)', $version->getProject(), $version->getVersionType());
            }
        );

        if ($this->getRequest()->isPost() && $form->isValid()) {
            if (isset($data['delete'])) {
                $this->projectService->delete($feedback);
                $this->flashMessenger()->addSuccessMessage(
                    \sprintf($this->translator->translate('txt-feedback-has-successfully-been-deleted'))
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
                        'zfcadmin/project/project/view',
                        ['id' => $feedback->getVersion()->getProject()->getId()],
                        ['fragment' => 'evaluation']
                    );
            }

            $feedback = $form->getData();

            $this->projectService->save($feedback);

            return $this->redirect()
                ->toRoute(
                    'zfcadmin/project/project/view',
                    ['id' => $feedback->getVersion()->getProject()->getId()],
                    ['fragment' => 'evaluation']
                );
        }

        return new ViewModel(['form' => $form]);
    }

    public function viewAction(): ViewModel
    {
        $feedback = $this->projectService->find(Feedback::class, (int)$this->params('id'));

        return new ViewModel(
            [
                'feedback'       => $feedback,
                'projectService' => $this->projectService,
                'versionService' => $this->versionService,
            ]
        );
    }
}
