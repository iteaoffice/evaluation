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

namespace Evaluation\Controller\Report;

use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as PaginatorAdapter;
use Evaluation\Controller\Plugin\GetFilter;
use Evaluation\Entity\Report\Window;
use Evaluation\Form\Report\WindowFilter;
use Evaluation\Service\EvaluationReportService;
use Evaluation\Service\FormService;
use Laminas\Http\Request;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Paginator\Paginator;
use Laminas\View\Model\ViewModel;

/**
 * @method GetFilter getEvaluationFilter()
 * @method FlashMessenger flashMessenger()
 */
final class WindowController extends AbstractActionController
{
    private EvaluationReportService $evaluationReportService;
    private FormService $formService;
    private TranslatorInterface $translator;

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
        $query        = $this->evaluationReportService->findFiltered(Window::class, $filterPlugin->getFilter());
        $paginator    = new Paginator(new PaginatorAdapter(new ORMPaginator($query, false)));
        $paginator::setDefaultItemCountPerPage(($page === 'all') ? PHP_INT_MAX : 20);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange(\ceil($paginator->getTotalItemCount() / $paginator::getDefaultItemCountPerPage()));

        $form = new WindowFilter();
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
        $window = $this->evaluationReportService->find(Window::class, (int)$this->params('id'));

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
        $data = $request->getPost()->toArray();
        $form = $this->formService->prepare(new Window(), $data);
        $form->setInputFilter(new \Evaluation\InputFilter\Report\WindowFilter());
        $form->remove('delete');

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return  $this->redirect()->toRoute('zfcadmin/evaluation/report/window/list');
            }

            if ($form->isValid()) {
                /** @var Window $window */
                $window = $form->getData();
                $this->evaluationReportService->save($window);
                $this->flashMessenger()->addSuccessMessage(
                    $this->translator->translate('txt-evaluation-report-window-has-successfully-been-saved')
                );
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report/window/view',
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
        $window = $this->evaluationReportService->find(Window::class, (int)$this->params('id'));

        if ($window === null) {
            return $this->notFoundAction();
        }

        $data = $request->getPost()->toArray();
        $form = $this->formService->prepare($window, $data);
        $form->setInputFilter(new \Evaluation\InputFilter\Report\WindowFilter());
        $form->remove('delete');

        if ($request->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute('zfcadmin/evaluation/report/window/list');
            }

            if ($form->isValid()) {
                /** Window $window */
                $window = $form->getData();
                $this->evaluationReportService->save($window);
                $this->flashMessenger()->addSuccessMessage(
                    $this->translator->translate('txt-evaluation-report-window-has-successfully-been-saved')
                );
                return $this->redirect()->toRoute(
                    'zfcadmin/evaluation/report/window/view',
                    ['id' => $window->getId()]
                );
            }
        }

        return new ViewModel(
            [
                'form'   => $form,
                'window' => $window
            ]
        );
    }
}
