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

namespace Evaluation\Controller\Plugin;

use Affiliation\Service\AffiliationService;
use General\Entity\Country;
use General\Service\CountryService;
use Evaluation\Entity\Evaluation;
use Evaluation\Entity\Type;
use InvalidArgumentException;
use Project\Entity\Funding\Status;
use Project\Entity\Project;
use Project\Entity\Version\Type as VersionType;
use Project\Entity\Version\Version;
use Project\Form\MatrixFilter;
use Evaluation\Service\EvaluationService;
use Project\Service\ProjectService;
use Project\Service\VersionService;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use function round;
use function ucwords;

/**
 * Class CreateEvaluation
 * @package Evaluation\Controller\Plugin
 */
final class CreateEvaluation extends AbstractPlugin
{
    /**
     * @var ProjectService
     */
    private $projectService;
    /**
     * @var VersionService
     */
    private $versionService;
    /**
     * @var EvaluationService
     */
    private $evaluationService;
    /**
     * @var AffiliationService
     */
    private $affiliationService;
    /**
     * @var CountryService
     */
    private $countryService;

    public function __construct(
        ProjectService     $projectService,
        VersionService     $versionService,
        EvaluationService  $evaluationService,
        AffiliationService $affiliationService,
        CountryService     $countryService
    ) {
        $this->projectService     = $projectService;
        $this->versionService     = $versionService;
        $this->evaluationService  = $evaluationService;
        $this->affiliationService = $affiliationService;
        $this->countryService     = $countryService;
    }

    public function __invoke(array $projects, Type $evaluationType, int $display, int $source): array
    {
        $evaluationResult = [];
        $countries = [];

        /** @var Project $project */
        foreach ($projects as $project) {
            foreach ($this->countryService->findCountryByProject(
                $project,
                AffiliationService::WHICH_ONLY_ACTIVE
            ) as $country) {
                $iso3 = $country->getIso3();
                /*
                 * Create an array of countries to serialize it normally
                 */
                $countries[$iso3] = [
                    'id'      => $country->getId(),
                    'country' => $country->getCountry(),
                    'object'  => $country,
                    'iso3'    => ucwords($iso3),

                ];
                $evaluation = $this->evaluationService
                    ->findEvaluationByCountryAndTypeAndProject(
                        $country,
                        $evaluationType,
                        $project
                    );
                /*
                 * Create a default status for not findable statuses
                 */
                if (null === $evaluation) {
                    $evaluation = new Evaluation();
                    /**
                     * @var $evaluationStatus Status
                     */
                    $evaluationStatus = $this->projectService->find(Status::class, 8);
                    $evaluation->setStatus($evaluationStatus);
                } else {
                    /*
                     * Remove the unwanted coupled entities from the object to avoid problems with serialization
                     * and to keep the evaluation-object small. The relevant information is already stored
                     * in the array and is not needed anymore in the evaluation object itself
                     */
                    $evaluation = clone $evaluation;
                    $evaluation->setContact(null);
                    $evaluation->setProject(null);
                    $evaluation->setCountry(null);
                }
                //Find the latest version
                $versionType = new VersionType();
                $versionType->setId($evaluationType->getId());

                /*
                 * This goes wrong for the funding-status because the VersionType === 3 matches the CR which is of course
                 * not always present
                 */
                if ($this->evaluationService->isEvaluation($evaluationType)) {
                    $version = $this->versionService->findLatestVersionByType($project, $versionType);
                } else {
                    $version = $this->projectService->getLatestProjectVersion($project);
                }

                /*
                 * We save the country locally to avoid very long calls.
                 * The country is also needed as separator
                 */
                $evalByProjectAndCountry = [];
                $evalByProjectAndCountry['evaluation'] = $evaluation;
                $evalByProjectAndCountry['result'] = [
                    'id'          => $evaluation->getStatus()->getId(),
                    'cssName'     => $evaluation->getStatus()->parseCssName(),
                    'title'       => $this->evaluationService->isEvaluation($evaluationType)
                        ? $evaluation->getStatus()->getStatusEvaluation()
                        : $evaluation->getStatus()->getStatusFunding(),
                    'is_decision' => $this->evaluationService->isDecision($evaluation->getStatus()),
                    'description' => $evaluation->getDescription(),
                ];

                $countryOfProjectLeader = null;
                if ($project->getContact()->hasOrganisation()) {
                    $countryOfProjectLeader = $project->getContact()->getContactOrganisation()->getOrganisation()
                        ->getCountry();
                }

                $evalByProjectAndCountry['isProjectLeader'] = (null !== $countryOfProjectLeader
                    && $countryOfProjectLeader->getId() === $country->getId());

                $value = $this->getValueFromDisplay($display, $source, $version, $project, $country);

                $evalByProjectAndCountry['value'] = $value;
                /*
                 * The evaluation is now an array which contains the evaluation object as first element (with 0 as index)
                 * and partners etc as secondary objects
                 */
                $evaluationResult[$country->getId()][$project->getId()] = $evalByProjectAndCountry;
            }
        }

        ksort($countries);

        $evaluationResult['countries'] = $countries;

        return $evaluationResult;
    }

    private function getValueFromDisplay(
        int $display,
        int $source,
        ?Version $version,
        Project $project,
        Country $country
    ): float {
        switch ($display) {
            case Evaluation::DISPLAY_PARTNERS:
                if ($source === MatrixFilter::SOURCE_VERSION) {
                    if (null === $version) {
                        throw new InvalidArgumentException('Version cannot be null');
                    }

                    //We take them all, because we want to know how many there are in the version
                    $amountOfPartners = $this->affiliationService
                        ->findAmountOfAffiliationByProjectVersionAndCountryAndWhich(
                            $version,
                            $country,
                            AffiliationService::WHICH_ALL
                        );
                } else {
                    $amountOfPartners = $this->affiliationService
                        ->findAmountOfAffiliationByProjectAndCountryAndWhich($project, $country);
                }

                $value = 0;
                if ($amountOfPartners > 0) {
                    $value = $amountOfPartners;
                }
                break;
            case Evaluation::DISPLAY_COST:
                if ($source === MatrixFilter::SOURCE_VERSION) {
                    /*
                     * Check if we have already a version
                     */
                    $cost = null;
                    if (null !== $version) {
                        $cost = $this->versionService
                            ->findTotalCostVersionByProjectVersionAndCountry($version, $country);
                    }
                } else {
                    $cost = $this->projectService->findTotalCostByProjectAndCountry($project, $country);
                }
                $value = round($cost / 1000000, 1); //in Meuros
                break;
            case Evaluation::DISPLAY_EFFORT:
                if ($source === MatrixFilter::SOURCE_VERSION) {
                    /*
                     * Check if we have already a version
                     */
                    $effort = null;
                    if (null !== $version) {
                        $effort = $this->versionService
                            ->findTotalEffortVersionByProjectVersionAndCountry($version, $country);
                    }
                } else {
                    $effort = $this->projectService->findTotalEffortByProjectAndCountry($project, $country);
                }

                $value = round($effort, 1);
                break;
            case Evaluation::DISPLAY_EFFORT_PERCENTAGE:
                if ($source === MatrixFilter::SOURCE_VERSION) {
                    $value = null;
                    if (null !== $version) {
                        $totalEffort = $this->versionService->findTotalEffortVersionByProjectVersion($version);
                        $effort = $this->versionService
                            ->findTotalEffortVersionByProjectVersionAndCountry($version, $country);
                        $value = round($effort * 100 / $totalEffort, 0);
                    }
                } else {
                    $totalEffort = array_sum($this->projectService->findTotalEffortByProjectPerYear($project));
                    $effort = $this->projectService->findTotalEffortByProjectAndCountry($project, $country);
                    $value = round($effort * 100 / $totalEffort, 0);
                }
                break;
            default:
                $value = '-';
                break;
        }

        return $value;
    }
}
