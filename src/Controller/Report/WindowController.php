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

namespace Evaluation\Controller\Report;

use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as PaginatorAdapter;
use Project\Controller\Plugin\GetFilter;
use Project\Entity\Evaluation\Report2\Window;
use Project\Form;
use Project\Service\EvaluationReport2Service;
use Project\Service\FormService;
use Zend\Http\Request;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;

/**
 * Class WindowController
 *
 * @method GetFilter getProjectFilter()
 * @package Evaluation\Controller\Report
 */
final class WindowController extends AbstractActionController
{
    /**
     * @var EvaluationReport2Service
     */
    private $evaluationReportService;
    /**
     * @var FormService
     */
    private $formService;

    public function __construct(
        EvaluationReport2Service $evaluationReportService,
        FormService $formService
    ) {
        $this->evaluationReportService = $evaluationReportService;
        $this->formService             = $formService;
    }

    public function listAction()
    {
        $page         = $this->params()->fromRoute('page', 1);
        $filterPlugin = $this->getProjectFilter();
        $query        = $this->evaluationReportService->findFiltered(Window::class, $filterPlugin->getFilter());
        $paginator    = new Paginator(new PaginatorAdapter(new ORMPaginator($query, false)));
        $paginator::setDefaultItemCountPerPage(($page === 'all') ? PHP_INT_MAX : 20);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange(\ceil($paginator->getTotalItemCount() / $paginator::getDefaultItemCountPerPage()));

        $form = new Form\Evaluation\Criterion\TopicFilter();
        $form->setData(['filter' => $filterPlugin->getFilter()]);

        return new ViewModel([
            'paginator'     => $paginator,
            'form'          => $form,
            'encodedFilter' => \urlencode($filterPlugin->getHash()),
            'order'         => $filterPlugin->getOrder(),
            'direction'     => $filterPlugin->getDirection(),
        ]);
    }

    public function viewAction(): ViewModel
    {
        $window = $this->evaluationReportService->find(Window::class, (int) $this->params('id'));

        if ($window === null) {
            return $this->notFoundAction();
        }

        return new ViewModel([
            'window' => $window
        ]);
    }

    public function newAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $data    = $request->getPost()->toArray();
        $form    = $this->formService->prepare(new Window(), $data);
        $form->remove('delete');

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                $this->redirect()->toRoute('zfcadmin/evaluation/report2/window/list');
            }

            if ($form->isValid()) {
                /** @var Window $window */
                $window = $form->getData();
                $this->evaluationReportService->save($window);
                $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report2/window/view',
                    ['id' => $window->getId()]
                );
            }
        }

        return new ViewModel(['form' => $form]);
    }

    public function editAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        /** @var Window $window */
        $window = $this->evaluationReportService->find(Window::class, (int) $this->params('id'));

        if ($window === null) {
            return $this->notFoundAction();
        }

        $data = $request->getPost()->toArray();
        $form = $this->formService->prepare($window, $data);
        $form->remove('delete');

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute('zfcadmin/evaluation/report2/window/list');
            }

            if ($form->isValid()) {
                /** Window $window */
                $window = $form->getData();
                $this->evaluationReportService->save($window);
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report2/window/view',
                    ['id' => $window->getId()]
                );
            }
        }

        return new ViewModel([
            'form'   => $form,
            'window' => $window
        ]);
    }
}
