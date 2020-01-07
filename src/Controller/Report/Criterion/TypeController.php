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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as PaginatorAdapter;
use Evaluation\Controller\Plugin\GetFilter;
use Evaluation\Entity\Report\Criterion\Type;
use Evaluation\Form\Report\Criterion\TypeFilter;
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
 */
final class TypeController extends AbstractActionController
{
    private EvaluationReportService $evaluationReportService;
    private FormService $formService;
    private EntityManager $entityManager;

    public function __construct(
        EvaluationReportService $evaluationReportService,
        FormService             $formService,
        EntityManager           $entityManager
    ) {
        $this->evaluationReportService = $evaluationReportService;
        $this->formService             = $formService;
        $this->entityManager           = $entityManager;
    }

    public function listAction()
    {
        $page         = $this->params()->fromRoute('page', 1);
        $filterPlugin = $this->getEvaluationFilter();
        $query        = $this->evaluationReportService->findFiltered(Type::class, $filterPlugin->getFilter());

        $paginator = new Paginator(new PaginatorAdapter(new ORMPaginator($query, false)));
        $paginator::setDefaultItemCountPerPage(($page === 'all') ? PHP_INT_MAX : 20);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange(ceil($paginator->getTotalItemCount() / $paginator::getDefaultItemCountPerPage()));

        $form = new TypeFilter($this->entityManager);
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
        $type = $this->evaluationReportService->find(Type::class, (int)$this->params('id'));

        if ($type === null) {
            return $this->notFoundAction();
        }

        return new ViewModel([
            'type' => $type
        ]);
    }

    public function newAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $data    = $request->getPost()->toArray();
        $form    = $this->formService->prepare(new Type(), $data);
        $form->remove('delete');

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute('zfcadmin/evaluation/report/criterion/type/list');
            }

            if ($form->isValid()) {
                /* @var $type Type */
                $type = $form->getData();
                $this->evaluationReportService->save($type);
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report/criterion/type/view',
                    ['id' => $type->getId()]
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
        /** @var Type $type */
        $type    = $this->evaluationReportService->find(Type::class, (int)$this->params('id'));
        $data    = $request->getPost()->toArray();
        $form    = $this->formService->prepare($type, $data);

        if (! $this->evaluationReportService->typeIsDeletable($type)) {
            $form->remove('delete');
        }

        if ($type === null) {
            return $this->notFoundAction();
        }

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute('zfcadmin/evaluation/report/criterion/type/list');
            }

            if (isset($data['delete'])) {
                $this->evaluationReportService->delete($type);
                return $this->redirect()->toRoute('zfcadmin/evaluation/report/criterion/type/list');
            }

            if ($form->isValid()) {
                /** @var Type $type */
                $type = $form->getData();
                $this->evaluationReportService->save($type);
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report/criterion/type/view',
                    ['id' => $type->getId()]
                );
            }
        }

        return new ViewModel([
            'form' => $form,
            'type' => $type
        ]);
    }
}
