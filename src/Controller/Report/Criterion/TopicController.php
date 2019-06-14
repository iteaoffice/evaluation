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

namespace Evaluation\Controller\Report\Criterion;

use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as PaginatorAdapter;
use Evaluation\Controller\Plugin\GetFilter;
use Evaluation\Entity\Report\Criterion\Topic;
use Evaluation\Form\Report\Criterion\TopicFilter;
use Evaluation\Service\EvaluationReportService;
use Evaluation\Service\FormService;
use Zend\Http\Request;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;
use function ceil;
use function urlencode;

/**
 * Class TopicController
 *
 * @method GetFilter getEvaluationFilter()
 * @method FlashMessenger flashMessenger()
 * @package Evaluation\Controller\Report
 */
final class TopicController extends AbstractActionController
{
    /**
     * @var EvaluationReportService
     */
    private $evaluationReportService;

    /**
     * @var FormService
     */
    private $formService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        EvaluationReportService $evaluationReportService,
        FormService             $formService,
        TranslatorInterface     $translator
    ) {
        $this->evaluationReportService = $evaluationReportService;
        $this->formService             = $formService;
        $this->translator              = $translator;
    }

    public function listAction()
    {
        $page         = $this->params()->fromRoute('page', 1);
        $filterPlugin = $this->getEvaluationFilter();
        $query        = $this->evaluationReportService->findFiltered(Topic::class, $filterPlugin->getFilter());

        $paginator = new Paginator(new PaginatorAdapter(new ORMPaginator($query, false)));
        $paginator::setDefaultItemCountPerPage(($page === 'all') ? PHP_INT_MAX : 20);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange(ceil($paginator->getTotalItemCount() / $paginator::getDefaultItemCountPerPage()));

        $form = new TopicFilter();
        $form->setData(['filter' => $filterPlugin->getFilter()]);

        return new ViewModel([
            'paginator'     => $paginator,
            'form'          => $form,
            'encodedFilter' => urlencode($filterPlugin->getHash()),
            'order'         => $filterPlugin->getOrder(),
            'direction'     => $filterPlugin->getDirection(),
        ]);
    }

    public function viewAction()
    {
        $topic = $this->evaluationReportService->find(Topic::class, (int)$this->params('id'));

        if ($topic === null) {
            return $this->notFoundAction();
        }

        return new ViewModel([
            'topic' => $topic
        ]);
    }

    public function newAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $data    = $request->getPost()->toArray();
        $form    = $this->formService->prepare(new Topic(), $data);
        $form->remove('delete');

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute('zfcadmin/evaluation/report2/criterion/topic/list');
            }

            if ($form->isValid()) {
                /* @var $topic Topic */
                $topic = $form->getData();
                $this->evaluationReportService->save($topic);
                $this->flashMessenger()->addSuccessMessage(
                    $this->translator->translate('txt-evaluation-report-criterion-topic-has-successfully-been-saved')
                );
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report2/criterion/topic/view',
                    ['id' => $topic->getId()]
                );
            }
        }

        return new ViewModel(['form' => $form]);
    }

    public function editAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        /** @var Topic $topic */
        $topic = $this->evaluationReportService->find(Topic::class, (int)$this->params('id'));

        if ($topic === null) {
            return $this->notFoundAction();
        }

        $data = $request->getPost()->toArray();
        $form = $this->formService->prepare($topic, $data);
        if ($topic->getVersionTopics()->count() > 0) {
            $form->remove('delete');
        }

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute('zfcadmin/evaluation/report2/criterion/topic/list');
            }

            if (isset($data['delete'])) {
                $this->evaluationReportService->delete($topic);
                $this->flashMessenger()->addSuccessMessage(
                    $this->translator->translate('txt-evaluation-report-criterion-topic-has-successfully-been-deleted')
                );
                return $this->redirect()->toRoute('zfcadmin/evaluation/report2/criterion/topic/list');
            }

            if ($form->isValid()) {
                /** @var Topic $topic */
                $topic = $form->getData();
                $this->evaluationReportService->save($topic);
                $this->flashMessenger()->addSuccessMessage(
                    $this->translator->translate('txt-evaluation-report-criterion-topic-has-successfully-been-saved')
                );
                $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report2/criterion/topic/view',
                    ['id' => $topic->getId()]
                );
            }
        }

        return new ViewModel([
            'form'  => $form,
            'topic' => $topic
        ]);
    }
}
