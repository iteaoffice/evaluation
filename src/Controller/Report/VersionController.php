<?php
/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @topic       Project
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2017 ITEA Office (https://itea3.org)
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
use Evaluation\Entity\Report;
use Evaluation\Entity\Report\Criterion\Version as CriterionVersion;
use Evaluation\Entity\Report\Version;
use Evaluation\Form\Report\VersionFilter;
use Evaluation\Service\EvaluationReportService;
use Evaluation\Service\FormService;
use Zend\Http\Request;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;

/**
 * Class VersionController
 *
 * @method GetFilter getProjectFilter()
 * @package Evaluation\Controller\Report
 */
final class VersionController extends AbstractActionController
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EvaluationReportService
     */
    private $evaluationReportService;

    /**
     * @var FormService
     */
    private $formService;

    public function __construct(
        EntityManager           $entityManager,
        EvaluationReportService $evaluationReportService,
        FormService             $formService
    ) {
        $this->entityManager           = $entityManager;
        $this->evaluationReportService = $evaluationReportService;
        $this->formService             = $formService;
    }

    public function listAction()
    {
        $page         = $this->params('page', 1);
        $filterPlugin = $this->getProjectFilter();
        $query        = $this->evaluationReportService->findFiltered(Version::class, $filterPlugin->getFilter());

        $paginator = new Paginator(new PaginatorAdapter(new ORMPaginator($query, false)));
        $paginator::setDefaultItemCountPerPage(($page === 'all') ? PHP_INT_MAX : 20);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange(\ceil($paginator->getTotalItemCount() / $paginator::getDefaultItemCountPerPage()));

        $form = new VersionFilter($this->entityManager);
        $form->setData(['filter' => $filterPlugin->getFilter()]);

        return new ViewModel([
            'paginator'     => $paginator,
            'form'          => $form,
            'encodedFilter' => \urlencode($filterPlugin->getHash()),
            'order'         => $filterPlugin->getOrder(),
            'direction'     => $filterPlugin->getDirection(),
        ]);
    }

    public function viewAction()
    {
        /** @var Version $reportVersion */
        $reportVersion = $this->evaluationReportService->find(Version::class, (int) $this->params('id'));

        if ($reportVersion === null) {
            return $this->notFoundAction();
        }

        return new ViewModel([
            'reportVersion'  => $reportVersion,
            'reports'        => $this->evaluationReportService->count(Report::class, ['version' => $reportVersion]),
            'activeWindows'  =>  $this->entityManager->getRepository(Report\Window::class)
                ->findActiveWindows($reportVersion),
            'sortedCriteria' => $this->entityManager->getRepository(CriterionVersion::class)
                ->findSorted($reportVersion),
        ]);
    }

    public function newAction()
    {
        /** @var Request $request */
        $request       = $this->getRequest();
        $data          = $request->getPost()->toArray();
        $reportVersion = new Version();
        $form          = $this->formService->prepare($reportVersion, $data);
        $form->getInputFilter()->get($reportVersion->get('underscore_entity_name'))->get('topics')
            ->setRequired(false);
        $form->remove('delete');

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                $this->redirect()->toRoute('zfcadmin/evaluation/report2/version/list');
            }

            if ($form->isValid()) {
                /** @var Version $reportVersion */
                $reportVersion = $form->getData();
                $this->evaluationReportService->save($reportVersion);
                $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report2/version/view',
                    ['id' => $reportVersion->getId()]
                );
            }
        }

        return new ViewModel([
            'form'   => $form
        ]);
    }

    public function editAction()
    {
        /** @var Request $request */
        $request       = $this->getRequest();
        /** @var Version $reportVersion */
        $reportVersion = $this->evaluationReportService->find(Version::class, (int) $this->params('id'));

        if ($reportVersion === null) {
            return $this->notFoundAction();
        }

        $hasReports = ($this->evaluationReportService->count(Report::class, ['version' => $reportVersion]) > 0);
        $data       = $request->getPost()->toArray();
        $form       = $this->formService->prepare($reportVersion, $data);
        $form->getInputFilter()->get($reportVersion->get('underscore_entity_name'))->get('topics')
            ->setRequired(false);
        if ($hasReports) {
            $form->remove('delete');
        }

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute('zfcadmin/evaluation/report2/version/list');
            }

            if (isset($data['delete']) && !$hasReports) {
                $this->evaluationReportService->delete($reportVersion);

                return $this->redirect()->toRoute('zfcadmin/evaluation/report2/version/list');
            }

            if ($form->isValid()) {
                /** @var Version $reportVersion */
                $reportVersion = $form->getData();
                $this->evaluationReportService->save($reportVersion);
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report2/version/view',
                    ['id' => $reportVersion->getId()]
                );
            }
        }

        return new ViewModel([
            'form'          => $form,
            'reportVersion' => $reportVersion
        ]);
    }

    public function copyAction()
    {
        /** @var Request $request */
        $request       = $this->getRequest();
        /** @var Version $reportVersion */
        $reportVersion = $this->evaluationReportService->find(Version::class, (int) $this->params('id'));

        if ($reportVersion === null) {
            return $this->notFoundAction();
        }

        $reportVersionCopy = $this->evaluationReportService->copyEvaluationReportVersion($reportVersion);
        $data              = $request->getPost()->toArray();
        $form              = $this->formService->prepare($reportVersionCopy, $data);
        $form->getInputFilter()->get($reportVersion->get('underscore_entity_name'))->get('topics')
            ->setRequired(false);
        $form->remove('delete');

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report2/version/view',
                    ['id' => $reportVersion->getId()]
                );
            }

            if ($form->isValid()) {
                /** @var Version $reportVersionCopy */
                $reportVersionCopy = $form->getData();
                $this->evaluationReportService->save($reportVersionCopy);
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report2/version/view',
                    ['id' => $reportVersionCopy->getId()]
                );
            }
        }

        return new ViewModel([
            'form'           => $form,
            'reportVersion'  => $reportVersion,
            'sortedCriteria' => $this->entityManager->getRepository(CriterionVersion::class)
                ->findSorted($reportVersion),
        ]);

    }
}
