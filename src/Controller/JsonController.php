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

use BjyAuthorize\Controller\Plugin\IsAllowed;
use Contact\Entity\Contact;
use Evaluation\Controller\Plugin\CreateEvaluation;
use Evaluation\Entity\Evaluation;
use Evaluation\Entity\Type;
use Evaluation\Service\EvaluationService;
use Evaluation\View\Helper\EvaluationLink;
use General\Entity\Country;
use General\Service\CountryService;
use Program\Entity\Call\Call;
use Program\Service\CallService;
use Project\Entity\Funding\Status;
use Project\Entity\Project;
use Project\Entity\Version\Type as VersionType;
use Project\Form\MatrixFilter;
use Project\Service\ProjectService;
use Project\View\Helper\ProjectLink;
use Project\View\Helper\ProjectStatusIcon;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Plugin\Identity\Identity;
use Zend\View\HelperPluginManager;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

/**
 * @package Evaluation\Controller
 * @method CreateEvaluation createEvaluation(array $projects, Type $evaluationType, int $display, int $source)
 * @method Identity|Contact identity()
 * @method IsAllowed isAllowed($resource, string $action)
 */
final class JsonController extends AbstractActionController
{
    /**
     * @var CallService
     */
    private $callService;
    /**
     * @var EvaluationService
     */
    private $evaluationService;
    /**
     * @var ProjectService
     */
    private $projectService;
    /**
     * @var CountryService
     */
    private $countryService;
    /**
     * @var HelperPluginManager
     */
    private $viewHelperManager;

    public function __construct(
        CallService $callService,
        EvaluationService $evaluationService,
        ProjectService $projectService,
        CountryService $countryService,
        HelperPluginManager $viewHelperManager
    ) {
        $this->callService = $callService;
        $this->evaluationService = $evaluationService;
        $this->projectService = $projectService;
        $this->countryService = $countryService;
        $this->viewHelperManager = $viewHelperManager;
    }

    public function evaluationAction(): ViewModel
    {
        $display = $this->params()->fromQuery('display', Evaluation::DISPLAY_PARTNERS);
        $callId = $this->params()
            ->fromQuery('call', $this->callService->findFirstAndLastCall()->lastCall->getId());

        $source = $this->params()->fromQuery('source', MatrixFilter::SOURCE_VERSION);
        $evaluationTypeId = (int)$this->params()->fromQuery('type', 1);
        /**
         * @var $evaluationType Type
         */
        $evaluationType = $this->evaluationService->find(Type::class, $evaluationTypeId);
        /** @var VersionType $versionType */
        $versionType = $this->projectService
            ->find(VersionType::class, $evaluationType->getVersionType());

        /** @var Call $call */
        $call = $this->callService->findCallById((int)$callId);

        switch ($evaluationType->getId()) {
            case Type::TYPE_PO_EVALUATION:
                //PO and FPP are the same in this case
            case Type::TYPE_FPP_EVALUATION:
                /*
                 * Collect the data add it in the matrix
                 */
                $projects = $this->projectService->findProjectsByCallAndVersionType($call, $versionType);
                break;
            case Type::TYPE_FUNDING_STATUS:
            default:
                $which = ProjectService::WHICH_ONLY_ACTIVE;

                if (null !== $call) {
                    $projects = $this->projectService->findProjectsByCall($call, $which)->getQuery()->getResult();
                } else {
                    $projects = $this->projectService->findAllProjects($which)->getResult();
                }
                break;
        }

        $evaluationResult = $this->createEvaluation($projects, $evaluationType, $display, $source);

        /** @var ProjectLink $projectLink */
        $projectLink = $this->viewHelperManager->get(ProjectLink::class);
        /** @var ProjectStatusIcon $projectStatusIcon */
        $projectStatusIcon = $this->viewHelperManager->get(ProjectStatusIcon::class);
        /** @var EvaluationLink $evaluationLink */
        $evaluationLink = $this->viewHelperManager->get(EvaluationLink::class);
        /** @var Country $contactCountry */
        $contactCountry = $this->identity()->getContactOrganisation()->getOrganisation()
            ->getCountry();

        //Create an array with projects having the key and the link to the project
        $projectResults = [];

        /** @var Project $project */
        foreach ($projects as $project) {
            $projectResult = [];
            $projectResult['id'] = $project->getId();

            /*
             * Produce the link to the project (or give just the project-name when this is empty)
             */
            $link = $projectLink($project, 'view-community', 'name');
            if (!$this->isAllowed($project, 'view-community')) {
                $projectResult['link'] = $project->parseFullName();
            } else {
                $projectResult['link'] = $link;
            }

            $projectResult['icon'] = $projectStatusIcon($project);
            $projectResult['evaluationLink'] = $evaluationLink(
                null,
                $project,
                $evaluationType,
                $contactCountry,
                'overview-project',
                'icon'
            );
            $projectResult['evaluationDownload'] = $evaluationLink(
                null,
                $project,
                $evaluationType,
                $contactCountry,
                'download-project',
                'icon'
            );

            $projectResults[] = $projectResult;
        }

        return new JsonModel(
            [
                'isEvaluation'     => $this->evaluationService->isEvaluation($evaluationType),
                'projects'         => $projectResults,
                'source'           => $source,
                'countries'        => $evaluationResult['countries'],
                'evaluationResult' => $evaluationResult,
                'evaluationType'   => $evaluationType,
            ]
        );
    }

    public function updateEvaluationAction(): JsonModel
    {
        $countryId = $this->params()->fromPost('country');
        $evaluationId = $this->params()->fromPost('evaluation');
        $projectId = $this->params()->fromPost('project');
        $statusId = $this->params()->fromPost('status');

        /** @var Type $type */
        $type = $this->evaluationService->find(Type::class, Type::TYPE_FUNDING_STATUS);

        if (empty($evaluationId)) {
            $evaluation = new Evaluation();
            $evaluation->setType($type);
            $evaluation->setProject($this->projectService->findProjectById((int)$projectId));
            $evaluation->setCountry($this->countryService->findCountryById((int)$countryId));
            $evaluation->setContact($this->identity());
        } else {
            $evaluation = $this->evaluationService->find(Evaluation::class, (int)$evaluationId);
        }

        if (null !== $statusId) {
            /**
             * @var $status Status
             */
            $status = $this->projectService->find(Status::class, (int)$statusId);
            $evaluation->setStatus($status);
            $this->evaluationService->save($evaluation);

            return new JsonModel(
                [
                    'class' => $status->parseCssName(),
                    'code'  => $status->getCode(),
                ]
            );
        }

        return new JsonModel();
    }
}
