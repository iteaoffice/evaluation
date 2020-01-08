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

namespace Evaluation\Controller\Report\Criterion;

use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as PaginatorAdapter;
use Evaluation\Controller\Plugin\GetFilter;
use Evaluation\Entity\Report\Criterion\Category;
use Evaluation\Form\Report\Criterion\CategoryFilter;
use Evaluation\Service\EvaluationReportService;
use Evaluation\Service\FormService;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Paginator\Paginator;
use Laminas\View\Model\ViewModel;

use function ceil;
use function urlencode;

/**
 * @method GetFilter getEvaluationFilter()
 * @package Evaluation\Controller\Report\Criterion
 */
final class CategoryController extends AbstractActionController
{
    private EvaluationReportService $evaluationReportService;
    private FormService $formService;

    public function __construct(
        EvaluationReportService $evaluationReportService,
        FormService $formService
    ) {
        $this->evaluationReportService = $evaluationReportService;
        $this->formService             = $formService;
    }

    public function listAction(): ViewModel
    {
        $page = $this->params()->fromRoute('page', 1);
        $filterPlugin = $this->getEvaluationFilter();
        $query = $this->evaluationReportService->findFiltered(Category::class, $filterPlugin->getFilter());
        $paginator = new Paginator(new PaginatorAdapter(new ORMPaginator($query, false)));
        $paginator::setDefaultItemCountPerPage(($page === 'all') ? PHP_INT_MAX : 20);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange(ceil($paginator->getTotalItemCount() / $paginator::getDefaultItemCountPerPage()));

        $form = new CategoryFilter();
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
        $category = $this->evaluationReportService->find(Category::class, (int)$this->params('id'));
        if ($category === null) {
            return $this->notFoundAction();
        }

        return new ViewModel(['category' => $category]);
    }

    public function newAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $data = $request->getPost()->toArray();
        $form = $this->formService->prepare(new Category(), $data);
        $form->remove('delete');

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute('zfcadmin/evaluation/report/criterion/category/list');
            }

            if ($form->isValid()) {
                /* @var $category Category */
                $category = $form->getData();

                $this->evaluationReportService->save($category);
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report/criterion/category/view',
                    ['id' => $category->getId()]
                );
            }
        }

        return new ViewModel(['form' => $form]);
    }

    public function editAction()
    {
        /** @var Request $request */
        $request  = $this->getRequest();
        /** @var Category $category */
        $category = $this->evaluationReportService->find(Category::class, (int)$this->params('id'));

        if ($category === null) {
            return $this->notFoundAction();
        }

        $data = $request->getPost()->toArray();
        $form = $this->formService->prepare($category, $data);

        if (! $category->getTypes()->isEmpty()) {
            $form->remove('delete');
        }

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute('zfcadmin/evaluation/report/criterion/category/list');
            }

            if (isset($data['delete']) && $category->getTypes()->isEmpty()) {
                $this->evaluationReportService->delete($category);
                return $this->redirect()->toRoute('zfcadmin/evaluation/report/criterion/category/list');
            }

            if ($form->isValid()) {
                /** @var Category $category */
                $category = $form->getData();
                $this->evaluationReportService->save($category);
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report/criterion/category/view',
                    ['id' => $category->getId()]
                );
            }
        }

        return new ViewModel([
            'form'     => $form,
            'category' => $category
        ]);
    }
}
