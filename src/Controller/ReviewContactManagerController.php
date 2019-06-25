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

namespace Evaluation\Controller;

use Contact\Form\Element\Contact as ContactFormElement;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as PaginatorAdapter;
use Evaluation\Controller\Plugin\GetFilter;
use Evaluation\Form\Reviewer\ContactFilter;
use Evaluation\Service\FormService;
use Evaluation\Entity\Reviewer\Contact;
use Project\Service\ProjectService;
use Zend\Http\Request;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;
use function ceil;
use function urlencode;

/**
 * Class ReviewContactManagerController
 * @package Evaluation\Controller
 * @method GetFilter getEvaluationFilter()
 */
final class ReviewContactManagerController extends AbstractActionController
{
    /**
     * @var ProjectService
     */
    private $projectService;
    /**
     * @var FormService
     */
    private $formService;

    public function __construct(
        ProjectService $projectService,
        FormService    $formService
    ) {
        $this->projectService = $projectService;
        $this->formService    = $formService;
    }

    public function listAction(): ViewModel
    {
        $page         = $this->params()->fromRoute('page', 1);
        $filterPlugin = $this->getEvaluationFilter();
        $contactQuery = $this->projectService->findFiltered(Contact::class, $filterPlugin->getFilter());

        $paginator = new Paginator(new PaginatorAdapter(new ORMPaginator($contactQuery, false)));
        $paginator::setDefaultItemCountPerPage(($page === 'all') ? PHP_INT_MAX : 20);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange(ceil($paginator->getTotalItemCount() / $paginator::getDefaultItemCountPerPage()));

        $form = new ContactFilter();
        $form->setData(['filter' => $filterPlugin->getFilter()]);

        return new ViewModel([
            'paginator'     => $paginator,
            'form'          => $form,
            'encodedFilter' => urlencode($filterPlugin->getHash()),
            'order'         => $filterPlugin->getOrder(),
            'direction'     => $filterPlugin->getDirection(),
        ]);
    }

    public function viewAction(): ViewModel
    {
        /** @var Contact $reviewContact */
        $reviewContact = $this->projectService->find(Contact::class, (int)$this->params('id'));

        if (null === $reviewContact) {
            return $this->notFoundAction();
        }

        return new ViewModel(['reviewContact' => $reviewContact]);
    }

    public function newAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $data = $request->getPost()->toArray();

        $form = $this->formService->prepare(new Contact(), $data);
        $form->remove('delete');

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute('zfcadmin/evaluation/review/contact/list');
            }

            if ($form->isValid()) {
                /** @var Contact $reviewContact */
                $reviewContact = $form->getData();
                $this->projectService->save($reviewContact);

                return $this->redirect()->toRoute(
                    'zfcadmin/project/review/contact/view',
                    ['id' => $reviewContact->getId()]
                );
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }

    public function editAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        /** @var Contact $reviewContact */
        $reviewContact = $this->projectService->find(Contact::class, (int)$this->params('id'));
        $data          = $request->getPost()->toArray();
        $form          = $this->formService->prepare($reviewContact, $data);
        /** @var ContactFormElement $contactElement */
        $contactElement = $form->get($reviewContact->get('underscore_entity_name'))->get('contact');
        $contactElement->setValueOptions(
            [$reviewContact->getContact()->getId() => $reviewContact->getContact()->getDisplayName()]
        )->setDisableInArrayValidator(true);

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute('zfcadmin/evaluation/review/contact/list');
            }

            if (isset($data['delete'])) {
                $this->projectService->delete($reviewContact);
                return $this->redirect()->toRoute('zfcadmin/evaluation/review/contact/list');
            }

            if ($form->isValid()) {
                /** @var Contact $reviewContact */
                $reviewContact = $form->getData();
                $this->projectService->save($reviewContact);
                $this->redirect()->toRoute(
                    'zfcadmin/evaluation/review/contact/view',
                    ['id' => $reviewContact->getId()]
                );
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }
}
