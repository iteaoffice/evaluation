<?php
/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @topic       Project
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2019 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\Controller\Report;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as PaginatorAdapter;
use Evaluation\Controller\Plugin\GetFilter;
use Evaluation\Entity\Report\Criterion;
use Evaluation\Form\Report\CriterionFilter;
use Evaluation\Service\EvaluationReportService;
use Evaluation\Service\FormService;
use Laminas\Http\Request;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Paginator\Paginator;
use Laminas\View\Model\ViewModel;

/**
 * @method GetFilter getEvaluationFilter()
 */
final class CriterionController extends AbstractActionController
{
    private EvaluationReportService $evaluationReportService;
    private FormService $formService;
    private EntityManager $entityManager;
    private TranslatorInterface $translator;

    public function __construct(
        EvaluationReportService $evaluationReportService,
        FormService $formService,
        EntityManager $entityManager,
        TranslatorInterface $translator
    ) {
        $this->evaluationReportService = $evaluationReportService;
        $this->formService = $formService;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    public function listAction()
    {
        $page = $this->params()->fromRoute('page', 1);
        $filterPlugin = $this->getEvaluationFilter();
        $query = $this->evaluationReportService->findFiltered(Criterion::class, $filterPlugin->getFilter());

        $paginator = new Paginator(new PaginatorAdapter(new ORMPaginator($query, false)));
        $paginator::setDefaultItemCountPerPage(($page === 'all') ? PHP_INT_MAX : 20);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange(\ceil($paginator->getTotalItemCount() / $paginator::getDefaultItemCountPerPage()));

        $form = new CriterionFilter($this->entityManager);
        $form->setData(['filter' => $filterPlugin->getFilter()]);

        return new ViewModel([
            'paginator' => $paginator,
            'form' => $form,
            'encodedFilter' => urlencode($filterPlugin->getHash()),
            'order' => $filterPlugin->getOrder(),
            'direction' => $filterPlugin->getDirection(),
        ]);
    }

    public function viewAction()
    {
        $criterion = $this->evaluationReportService->find(Criterion::class, (int)$this->params('id'));

        if ($criterion === null) {
            return $this->notFoundAction();
        }

        return new ViewModel([
            'criterion' => $criterion,
            'versions' => $this->evaluationReportService->count(Criterion\Version::class, ['criterion' => $criterion])
        ]);
    }

    public function newAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $data = $request->getPost()->toArray();
        $form = $this->formService->prepare(new Criterion(), $data);
        $form->remove('delete');

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute('zfcadmin/evaluation/report/criterion/list');
            }

            if ($form->isValid()) {
                /** @var Criterion $criterion */
                $criterion = $form->getData();
                $this->evaluationReportService->save($criterion);
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report/criterion/view',
                    ['id' => $criterion->getId()]
                );
            }
        }

        return new ViewModel([
            'form' => $form,
        ]);
    }

    public function editAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        /** @var Criterion $criterion */
        $criterion = $this->evaluationReportService->find(Criterion::class, (int)$this->params('id'));

        if ($criterion === null) {
            return $this->notFoundAction();
        }

        $data = $request->getPost()->toArray();
        $form = $this->formService->prepare($criterion, $data);
        $hasVersions = ($this->evaluationReportService->count(
            Criterion\Version::class,
            ['criterion' => $criterion]
        ) > 0);
        if ($hasVersions) {
            $form->remove('delete');
        }

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute('zfcadmin/evaluation/report/criterion/list');
            }

            if (isset($data['delete']) && ! $hasVersions) {
                $this->evaluationReportService->delete($criterion);
                return $this->redirect()->toRoute('zfcadmin/evaluation/report/criterion/list');
            }

            if ($form->isValid()) {
                /** @var Criterion $criterion */
                $criterion = $form->getData();
                $this->evaluationReportService->save($criterion);
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report/criterion/view',
                    ['id' => $criterion->getId()]
                );
            }
        }

        return new ViewModel([
            'form' => $form,
            'criterion' => $criterion,
        ]);
    }
}
