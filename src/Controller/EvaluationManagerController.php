<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Controller;

use Contact\Entity\Contact;
use Doctrine\ORM\EntityManager;
use Evaluation\Entity\Evaluation;
use Evaluation\Entity\Type;
use Evaluation\Service\EvaluationService;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Program\Entity\Call\Call;
use Program\Service\CallService;
use Project\Entity\Version\Type as VersionType;
use Project\Form;
use Project\Service\ProjectService;

/**
 * @method array createEvaluation(array $projects, Type $evaluationType, int $display, int $source)
 * @method Contact identity()
 */
final class EvaluationManagerController extends AbstractActionController
{
    private ProjectService $projectService;
    private EvaluationService $evaluationService;
    private CallService $callService;
    private EntityManager $entityManager;

    public function __construct(
        ProjectService $projectService,
        EvaluationService $evaluationService,
        CallService $callService,
        EntityManager $entityManager
    ) {
        $this->projectService    = $projectService;
        $this->evaluationService = $evaluationService;
        $this->callService       = $callService;
        $this->entityManager     = $entityManager;
    }

    public function matrixAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $display = (int)$this->params('display', Evaluation::DISPLAY_PARTNERS);

        $callId = (int)$this->getEvent()->getRouteMatch()
            ->getParam('call', $this->callService->findFirstAndLastCall()->lastCall->getId());

        $source          = (int)$this->params('source', Form\MatrixFilter::SOURCE_VERSION);
        $typeId          = (int)$this->params('type', Type::TYPE_FUNDING_STATUS);
        $evaluationTypes = $this->evaluationService->findAll(Type::class);
        $versionTypes    = $this->projectService->findAll(VersionType::class);

        /*
         * The form can be used to overrule some parameters. We therefore need to check if the form is set
         * posted correctly and need to update the params when the form has been post
         */
        $form = new Form\MatrixFilter($this->entityManager);
        $form->setData($request->getPost()->toArray());

        if ($request->isPost() && $form->isValid()) {
            $formData = $form->getData();

            return $this->redirect()->toRoute(
                'zfcadmin/evaluation/matrix',
                [
                    'type'    => $typeId,
                    'source'  => (int)$formData['source'],
                    'call'    => (int)$formData['call'],
                    'display' => $display,
                ]
            );
        }

        $form->setData(
            [
                'call'   => $callId,
                'source' => $source,
            ]
        );

        /** @var Call $call */
        $call = $this->callService->findCallById((int)$callId);
        /** @var Type $evaluationType */
        $evaluationType  = $this->evaluationService->find(Type::class, $typeId);
        $fundingStatuses = $this->evaluationService->getFundingStatusList(
            $this->evaluationService->parseMainEvaluationType($evaluationType)
        );
        /** @var VersionType $versionType */
        $versionType = $this->projectService
            ->find(VersionType::class, $evaluationType->getVersionType());
        $contact     = $this->identity();

        switch ($evaluationType->getId()) {
            case Type::TYPE_PO_EVALUATION:
            case Type::TYPE_FPP_EVALUATION:
                // Collect the data add it in the matrix
                $projects = $this->projectService->findProjectsByCallAndVersionType($call, $versionType);
                break;
            case Type::TYPE_FUNDING_STATUS:
            default:
                $which = ProjectService::WHICH_ONLY_ACTIVE;

                if (null !== $call) {
                    $projects = $this->projectService->findProjectsByCallAndContact($call, $contact, $which);
                } else {
                    $projects = $this->projectService->findAllProjectsByContact($contact, $which);
                }
                break;
        }

        return new ViewModel(
            [
                'isEvaluation'     => $this->evaluationService->isEvaluation($evaluationType),
                'projects'         => $projects,
                'fundingStatuses'  => $fundingStatuses,
                'evaluationTypes'  => $evaluationTypes,
                'evaluationType'   => $evaluationType,
                'call'             => $call,
                'typeId'           => $typeId,
                'source'           => $source,
                'display'          => (int)$display,
                'form'             => $form,
                'evaluationResult' => $this->createEvaluation(
                    $projects,
                    $evaluationType,
                    $display,
                    $source
                ),
                'evaluation'       => new Evaluation(),
            ]
        );
    }
}
