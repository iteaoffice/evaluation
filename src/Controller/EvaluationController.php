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

namespace Evaluation\Controller;

use Contact\Entity\Contact;
use Contact\Service\ContactService;
use Doctrine\ORM\EntityManager;
use Evaluation\Controller\Plugin\CreateEvaluation;
use Evaluation\Controller\Plugin\RenderProjectEvaluation;
use Evaluation\Entity;
use Evaluation\Entity\Type;
use Evaluation\Service\EvaluationService;
use Evaluation\Service\FormService;
use General\Entity\Country;
use General\Service\CountryService;
use General\Service\GeneralService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Mvc\Plugin\Identity\Identity;
use Laminas\View\Model\ViewModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Program\Entity\Call\Call;
use Program\Service\CallService;
use Project\Entity\Project;
use Project\Entity\Version\Type as VersionType;
use Project\Entity\Version\Version;
use Project\Form\MatrixFilter;
use Project\Service\ProjectService;
use Project\Service\VersionService;

use function array_merge_recursive;
use function file_exists;
use function sprintf;
use function strlen;
use function strtoupper;
use function sys_get_temp_dir;

/**
 * @method CreateEvaluation createEvaluation(array $projects, Type $evaluationType, int $display, int $source)
 * @method Identity|Contact identity()
 * @method RenderProjectEvaluation renderProjectEvaluation()
 * @method FlashMessenger flashMessenger()
 */
final class EvaluationController extends AbstractActionController
{
    private ProjectService $projectService;
    private VersionService $versionService;
    private EvaluationService $evaluationService;
    private CallService $callService;
    private ContactService $contactService;
    private GeneralService $generalService;
    private CountryService $countryService;
    private FormService $formService;
    private EntityManager $entityManager;
    private TranslatorInterface $translator;

    public function __construct(
        ProjectService $projectService,
        VersionService $versionService,
        EvaluationService $evaluationService,
        CallService $callService,
        ContactService $contactService,
        GeneralService $generalService,
        CountryService $countryService,
        FormService $formService,
        EntityManager $entityManager,
        TranslatorInterface $translator
    ) {
        $this->projectService    = $projectService;
        $this->versionService    = $versionService;
        $this->evaluationService = $evaluationService;
        $this->callService       = $callService;
        $this->contactService    = $contactService;
        $this->generalService    = $generalService;
        $this->countryService    = $countryService;
        $this->formService       = $formService;
        $this->entityManager     = $entityManager;
        $this->translator        = $translator;
    }

    public function indexAction()
    {
        $call = $this->callService->findLastActiveCall();
        if (null === $call) {
            return $this->notFoundAction();
        }
        return $this->redirect()->toRoute('community/evaluation/overview', ['call' => $call->getId()]);
    }

    public function overviewAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $display = $this->params('display', Entity\Evaluation::DISPLAY_PARTNERS);
        $show    = $this->params('show', 'proposals');

        $source = $this->params('source', MatrixFilter::SOURCE_VERSION);
        $typeId = $this->params('type', 1);
        $callId = $this->params('call');

        $evaluationTypes = $this->evaluationService->findAll(Entity\Type::class);
        $versionTypes    = $this->projectService->findAll(VersionType::class);
        $projects        = [];

        /*
         * The form can be used to overrule some parameters. We therefore need to check if the form is set
         * posted correctly and need to update the params when the form has been post
         */
        $form = new MatrixFilter($this->entityManager);

        $form->setData($request->getPost()->toArray());

        if ($request->isPost() && $form->isValid()) {
            $formData = $form->getData();

            return $this->redirect()->toRoute(
                'community/evaluation/overview',
                [
                    'show'    => 'matrix',
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
        /** @var Entity\Type $evaluationType */
        $evaluationType  = $this->evaluationService->find(Entity\Type::class, (int)$typeId);
        $fundingStatuses = $this->evaluationService->getFundingStatusList(
            $this->evaluationService->parseMainEvaluationType($evaluationType)
        );
        /** @var VersionType $versionType */
        $versionType    = $this->projectService->find(
            VersionType::class,
            $evaluationType->getVersionType()
        );
        $viewParameters = [];

        switch ($show) {
            case 'proposals':
                $projects       = $this->projectService->findProjectsByCallAndVersionTypeAndContact(
                    $call,
                    $versionType,
                    $this->identity()
                );
                $viewParameters = [
                    'versionType' => $versionType,
                ];
                break;
            case 'matrix':
                switch ($evaluationType->getId()) {
                    case Entity\Type::TYPE_PO_EVALUATION:
                    case Entity\Type::TYPE_FPP_EVALUATION:
                        // Collect the data add it in the matrix
                        $projects = $this->projectService->findProjectsByCallAndVersionType($call, $versionType);
                        break;
                    case Entity\Type::TYPE_FUNDING_STATUS:
                    default:
                        $which = ProjectService::WHICH_ONLY_ACTIVE;

                        if (null !== $call) {
                            $projects = $this->projectService
                                ->findProjectsByCallAndContact(
                                    $call,
                                    $this->identity(),
                                    $which
                                );
                        } else {
                            $projects = $this->projectService
                                ->findAllProjectsByContact(
                                    $this->identity(),
                                    $which
                                );
                        }
                        break;
                }
                break;
        }

        return new ViewModel(
            array_merge_recursive(
                [
                    'isEvaluation'    => $this->evaluationService->isEvaluation($evaluationType),
                    'projects'        => $projects,
                    'fundingStatuses' => $fundingStatuses,
                    'evaluationTypes' => $evaluationTypes,
                    'versionTypes'    => $versionTypes,
                    'call'            => $call,
                    'contactCountry'  => $this->contactService->parseCountry($this->identity()),
                    'show'            => $show,
                    'typeId'          => $typeId,
                    'source'          => $source,
                    'display'         => (int)$display,
                    'form'            => $form,
                    'generalService'  => $this->generalService,
                    'projectService'  => $this->projectService,
                    'countryService'  => $this->countryService,
                    'evaluation'      => new Entity\Evaluation(),
                ],
                $viewParameters
            )
        );
    }

    public function downloadOverviewAction(): Response
    {
        $callId = $this->params('call', $this->callService->findFirstAndLastCall()->lastCall->getId());
        $typeId = $this->params('type', 1);

        /** @var Call $call */
        $call = $this->callService->findCallById((int)$callId);
        /** @var Entity\Type $evaluationType */
        $evaluationType = $this->evaluationService->find(Entity\Type::class, (int)$typeId);
        /** @var VersionType $versionType */
        $versionType = $this->projectService
            ->find(VersionType::class, $evaluationType->getVersionType());

        $fileName = sprintf('%s Evaluation Overview (%s).xlsx', $evaluationType, (string)$call);

        /*
         * delete the file
         */
        if (file_exists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName)) {
            unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName);
        }

        $xls = new Spreadsheet();
        $xls->getProperties()->setCreator('ITEA Office');
        $xls->getProperties()->setTitle(sprintf('Evaluation %s %s', $call, $evaluationType));
        $xls->getProperties()->setSubject('');
        $sheet = $xls->getActiveSheet();

        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(8);
        $sheet->getColumnDimension('E')->setWidth(35);
        $sheet->getColumnDimension('F')->setWidth(70);
        $sheet->getStyle('F')->getAlignment()->setWrapText(true);

        // Collect the data and add it to the excel
        $projects = $this->projectService->findProjectsByCallAndVersionType($call, $versionType);

        $row = 1;

        /** @var Project $project */
        foreach ($projects as $project) {
            $sheet->setCellValue('A' . $row, $project->parseFullName());
            $sheet->setCellValue('B' . $row, 'Country');
            $sheet->setCellValue('C' . $row, 'Eligibility');
            $sheet->setCellValue('D' . $row, 'Effort');
            $sheet->setCellValue('E' . $row, 'Percentage.');
            $sheet->setCellValue('F' . $row, 'Status');
            $sheet->setCellValue('G' . $row, 'Description');

            $cell = sprintf('A%s:G%s', $row, $row);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID);
            $sheet->getStyle($cell)->getFill()->getStartColor()->setRGB('CCCCCC');

            //Find the latest reviewed version of the version type
            /** @var Version $latestVersion */
            $latestVersion = $this->projectService->getLatestNotRejectedProjectVersion($project, $versionType);

            $totalEffort      = $this->versionService->findTotalEffortVersion($latestVersion);
            $evaluationResult = $this->createEvaluation(
                [$project],
                $evaluationType,
                Entity\Evaluation::DISPLAY_EFFORT,
                MatrixFilter::SOURCE_VERSION
            );

            //Create also a new row when a new country is added
            $row++;

            /*
             * Add now the countries to the excel
             */
            foreach ($this->countryService->findCountryByProject($project) as $country) {
                $projectEvaluation = $evaluationResult[$country->getId()][$project->getId()];
                /**
                 * @var $evaluation Entity\Evaluation
                 */
                $evaluation = $projectEvaluation['evaluation'];
                $value      = $projectEvaluation['value'];
                $sheet->getRowDimension($row)->setRowHeight(60);

                $sheet->setCellValue('B' . $row, (string)$country);
                $sheet->setCellValue(
                    'C' . $row,
                    $this->translator->translate($evaluation->getEligible(true))
                );
                $sheet->setCellValue('D' . $row, $value);
                if ($totalEffort != 0) {
                    $sheet->setCellValue(
                        'E' . $row,
                        sprintf("%s%%", number_format(($value / $totalEffort) * 100))
                    );
                }
                $sheet->setCellValue('F' . $row, $evaluation->getStatus()->getStatusFunding());
                $sheet->getStyle('F' . $row)->getFill()->setFillType(Fill::FILL_SOLID);
                $sheet->getStyle('F' . $row)->getFill()->getStartColor()->setRGB(
                    strtoupper($evaluation->getStatus()->getColor())
                );
                $sheet->setCellValue('G' . $row, $evaluation->getDescription());
                $sheet->getStyle('G' . $row)->getAlignment()->setWrapText(true);
                $sheet->getColumnDimension('G')->setWidth(60);
                $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $row++;
            }
        }

        $objWriter = IOFactory::createWriter($xls, 'Xlsx');
        $objWriter->save(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName);

        /** @var Response $response */
        $response = $this->getResponse();
        $response->getHeaders()
            ->addHeaderLine('Content-Disposition', 'attachment; filename="' . $fileName)
            ->addHeaderLine('Pragma: public')
            ->addHeaderLine('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->addHeaderLine('Content-Length: ' . filesize(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName));
        $response->setContent(file_get_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName));

        return $response;
    }

    public function evaluateProjectAction()
    {
        $routeMatch = $this->getEvent()->getRouteMatch();

        if (null === $routeMatch) {
            return $this->notFoundAction();
        }

        /** @var Country $country */
        $country = $this->generalService->find(Country::class, (int)$routeMatch->getParam('country'));

        if (null === $country) {
            return $this->notFoundAction();
        }

        /** @var Entity\Type $evaluationType */
        $evaluationType = $this->evaluationService
            ->find(Entity\Type::class, (int)$routeMatch->getParam('type'));
        $project        = $this->projectService->findProjectById((int)$routeMatch->getParam('project'));

        if (null === $project) {
            return $this->notFoundAction();
        }

        /** @var Entity\Type $evaluationTypes */
        $evaluationTypes = $this->evaluationService->findAll(Entity\Type::class);
        $data            = $this->getRequest()->getPost()->toArray();
        /*
         * The evaluation can be there, or be null, then we need to create it.
         */
        $evaluation = $this->evaluationService
            ->findEvaluationByCountryAndTypeAndProject($country, $evaluationType, $project);
        if (null === $evaluation) {
            $evaluation = new Entity\Evaluation();
            $evaluation->setProject($project);
            $evaluation->setContact($this->identity());
            $evaluation->setType($evaluationType);
            $evaluation->setCountry($country);
        }
        $form = $this->formService->prepare($evaluation, $data);

        /** Remove the fields not present in this view */
        $form->getInputFilter()->get('evaluation_entity_evaluation')->get('contact')->setRequired(false);
        $form->getInputFilter()->get('evaluation_entity_evaluation')->get('country')->setRequired(false);
        $form->getInputFilter()->get('evaluation_entity_evaluation')->get('type')->setRequired(false);
        $form->getInputFilter()->get('evaluation_entity_evaluation')->get('project')->setRequired(false);

        if ($this->getRequest()->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()->toRoute(
                    'community/evaluation/overview-project',
                    [
                        'country' => $country->getId(),
                        'type'    => $evaluationType->getId(),
                        'project' => $project->getId(),
                    ]
                );
            }

            if ($form->isValid()) {
                /** @var Entity\Evaluation $evaluation */
                $evaluation = $form->getData();
                $this->evaluationService->save($evaluation);

                return $this->redirect()->toRoute(
                    'community/evaluation/overview-project',
                    [
                        'country' => $country->getId(),
                        'type'    => $evaluationType->getId(),
                        'project' => $project->getId(),
                    ]
                );
            }
        }
        /*
         * Check to see if we have an active version
         */
        /** @var VersionType $versionType */
        $versionType = $this->versionService
            ->find(VersionType::class, $evaluationType->getVersionType());
        $version     = $this->versionService->findLatestVersionByType($project, $versionType);

        return new ViewModel(
            [
                'evaluation'      => $evaluation,
                'evaluationType'  => $evaluationType,
                'evaluationTypes' => $evaluationTypes,
                'version'         => $version,
                'form'            => $form,
            ]
        );
    }

    public function editAction()
    {
        /** @var Entity\Evaluation $evaluation */
        $evaluation = $this->evaluationService->find(Entity\Evaluation::class, (int)$this->params('id'));

        if (null === $evaluation) {
            return $this->notFoundAction();
        }

        $data = $this->getRequest()->getPost()->toArray();
        $form = $this->formService->prepare($evaluation, $data);
        $form->get($evaluation->get('underscore_entity_name'))->get('contact')->injectContact(
            $evaluation->getContact()
        );

        if ($this->getRequest()->isPost()) {
            if (isset($data['cancel'])) {
                return $this->redirect()
                    ->toRoute(
                        'zfcadmin/project/project/view',
                        ['id' => $evaluation->getProject()->getId()],
                        ['fragment' => 'evaluation']
                    );
            }

            if (isset($data['delete'])) {
                $this->evaluationService->delete($evaluation);

                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('txt-%s-evaluation-%s-has-successfully-been-updated'),
                        $evaluation->getType()->getType(),
                        $evaluation->getProject()
                    )
                );

                return $this->redirect()
                    ->toRoute(
                        'zfcadmin/project/project/view',
                        ['id' => $evaluation->getProject()->getId()],
                        ['fragment' => 'evaluation']
                    );
            }

            if ($form->isValid()) {
                /**
                 * @var $evaluation Entity\Evaluation
                 */
                $evaluation = $form->getData();
                $this->evaluationService->save($evaluation);

                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('txt-%s-evaluation-%s-has-successfully-been-updated'),
                        $evaluation->getType()->getType(),
                        $evaluation->getProject()
                    )
                );

                return $this->redirect()
                    ->toRoute(
                        'zfcadmin/project/project/evaluation',
                        ['id' => $evaluation->getProject()->getId()],
                        ['fragment' => 'type_' . $evaluation->getType()->getId()]
                    );
            }
        }

        return new ViewModel(['form' => $form, 'evaluation' => $evaluation]);
    }

    public function newAction()
    {
        $project = $this->projectService->findProjectById((int)$this->params('project'));

        if (null === $project) {
            return $this->notFoundAction();
        }

        $data = array_merge(
            ['evaluation' => ['project' => $project->getId()]],
            $this->getRequest()->getPost()->toArray()
        );

        $evaluation = new Entity\Evaluation();
        $form       = $this->formService->prepare($evaluation, $data);
        $form->setAttribute('class', 'form-horizontal');
        $form->remove('delete');

        $form->get($evaluation->get('underscore_entity_name'))->get('contact')->setDisableInArrayValidator(true);

        if ($this->getRequest()->isPost()) {
            if (null !== $this->getRequest()->getPost()->get('cancel')) {
                return $this->redirect()
                    ->toRoute(
                        'zfcadmin/project/project/view',
                        ['id' => $evaluation->getProject()->getId()],
                        ['fragment' => 'evaluation']
                    );
            }

            if ($form->isValid()) {
                /**
                 * @var $evaluation Entity\Evaluation
                 */
                $evaluation = $form->getData();
                $this->evaluationService->save($evaluation);

                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('txt-%s-evaluation-%s-has-successfully-been-created'),
                        $evaluation->getType()->getType(),
                        $evaluation->getProject()
                    )
                );

                return $this->redirect()
                    ->toRoute(
                        'zfcadmin/project/project/view',
                        ['id' => $evaluation->getProject()->getId()],
                        ['fragment' => 'evaluation']
                    );
            }
        }

        return new ViewModel(['form' => $form, 'evaluation' => $evaluation]);
    }

    public function overviewProjectAction(): ViewModel
    {
        $routeMatch = $this->getEvent()->getRouteMatch();
        if (null === $routeMatch) {
            return $this->notFoundAction();
        }
        $country = $this->generalService->find(Country::class, (int)$routeMatch->getParam('country'));
        /** @var Entity\Type $evaluationType */
        $evaluationType = $this->evaluationService
            ->find(Entity\Type::class, (int)$routeMatch->getParam('type'));
        $project        = $this->projectService->findProjectById((int)$routeMatch->getParam('project'));

        if (null === $project) {
            return $this->notFoundAction();
        }

        $evaluationTypes = $this->evaluationService->findAll(Entity\Type::class);
        $countries       = $this->countryService->findCountryByProject(
            $project
        );
        /*
         * Check to see if we have an active version
         */
        /** @var VersionType $versionType */
        $versionType      = $this->versionService
            ->find(VersionType::class, $evaluationType->getVersionType());
        $version          = $this->versionService->findLatestVersionByType($project, $versionType);
        $evaluationResult = $this->createEvaluation(
            [$project],
            $evaluationType,
            Entity\Evaluation::DISPLAY_EFFORT,
            MatrixFilter::SOURCE_VERSION
        );

        return new ViewModel(
            [
                'country'          => $country,
                'countries'        => $countries,
                'totalEffort'      => null !== $version ? $this->versionService->findTotalEffortVersion($version)
                    : 0,
                'contactCountry'   => $this->contactService->parseCountry(
                    $this->identity()
                ),
                'projectService'   => $this->projectService,
                'generalService'   => $this->generalService,
                'versionService'   => $this->versionService,
                'evaluationType'   => $evaluationType,
                'evaluationTypes'  => $evaluationTypes,
                'evaluationResult' => $evaluationResult,
                'version'          => $version,
                'project'          => $project,
                'versionType'      => $versionType,
            ]
        );
    }

    public function downloadProjectAction(): Response
    {
        $routeMatch = $this->getEvent()->getRouteMatch();
        /** @var Response $response */
        $response = $this->getResponse();

        if (null === $routeMatch) {
            return $response->setStatusCode(Response::STATUS_CODE_404);
        }

        /** @var Entity\Type $evaluationType */
        $evaluationType = $this->evaluationService
            ->find(Entity\Type::class, (int)$routeMatch->getParam('type'));
        $project        = $this->projectService->findProjectById((int)$routeMatch->getParam('project'));

        if (null === $project) {
            return $response->setStatusCode(Response::STATUS_CODE_404);
        }

        $countries = $this->countryService->findCountryByProject(
            $project
        );
        /*
         * Check to see if we have an active version
         */
        /** @var VersionType $versionType */
        $versionType      = $this->versionService
            ->find(VersionType::class, $evaluationType->getVersionType());
        $evaluationResult = $this->createEvaluation(
            [$project],
            $evaluationType,
            Entity\Evaluation::DISPLAY_EFFORT,
            MatrixFilter::SOURCE_VERSION
        );

        //Find the corresponding feedback
        $version = $this->versionService->findLatestVersionByType($project, $versionType);

        if (null === $version) {
            return $response->setStatusCode(Response::STATUS_CODE_404);
        }

        /*
         * Produce an overview of the evaluation
         */
        $projectEvaluationOverview = $this->renderProjectEvaluation()
            ->render(
                $project,
                $evaluationType,
                $versionType,
                $evaluationResult,
                $countries,
                $version->getFeedback()
            );


        $response->getHeaders()
            ->addHeaderLine(
                'Content-Disposition',
                'attachment; filename="evaluation-overview-' . $project->parseFullName() . '.pdf"'
            )
            ->addHeaderLine('Content-Type: application/pdf')
            ->addHeaderLine('Content-Length', strlen($projectEvaluationOverview->getPDFData()));
        $response->setContent($projectEvaluationOverview->getPDFData());

        return $response;
    }
}
