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

namespace Evaluation\Acl\Assertion;

use Admin\Entity\Access;
use DateInterval;
use DateTime;
use General\Entity\Country;
use General\Service\CountryService;
use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use Project\Acl\Assertion\Project;
use Evaluation\Entity\Evaluation;
use Evaluation\Entity\Type;
use Project\Entity\Version\Type as VersionType;
use Evaluation\Service\EvaluationService;
use Project\Service\ProjectService;
use Project\Service\VersionService;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;
use function in_array;

/**
 * Class EvaluationAssertion
 *
 * @package Evaluation\Acl\Assertion
 */
final class EvaluationAssertion extends AbstractAssertion
{
    /**
     * @var EvaluationService
     */
    private $evaluationService;
    /**
     * @var ProjectService
     */
    private $projectService;
    /**
     * @var VersionService;
     */
    private $versionService;
    /**
     * @var CountryService
     */
    private $countryService;
    /**
     * @var Project
     */
    private $projectAssertion;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->evaluationService = $container->get(EvaluationService::class);
        $this->projectService    = $container->get(ProjectService::class);
        $this->versionService    = $container->get(VersionService::class);
        $this->countryService    = $container->get(CountryService::class);
        $this->projectAssertion  = $container->get(Project::class);
    }

    public function assert(
        Acl               $acl,
        RoleInterface     $role = null,
        ResourceInterface $resource = null,
        $privilege = null
    ): bool {
        $countryId = null;
        $projectId = null;
        $evaluationTypeId = null;

        if ($this->hasRouteMatch()) {
            $countryId = $this->getRouteMatch()->getParam('country');
            $evaluationTypeId = $this->getRouteMatch()->getParam('type');
            $projectId = $this->getRouteMatch()->getParam('project');
        }

        $this->setPrivilege($privilege);

        /*
         * You need to be a funder, to see the overview. Return null if this ia not the case
         */
        if (in_array($this->getPrivilege(), ['index', 'overview', 'overview-project', 'download-overview'], true)) {
            if (!$this->rolesHaveAccess(['funder', 'office', 'ppa', 'steeringgroup'])) {
                return false;
            }

            //Stop the script here as we do not need to to know the rest of the evaluation
            return true;
        }


        if (!$resource instanceof Evaluation) {
            if (null === $countryId || null === $evaluationTypeId || null === $projectId) {
                return false;
            }
            /** @var Country $country */
            $country = $this->countryService->find(Country::class, (int)$countryId);
            /** @var Type $evaluationType */
            $evaluationType = $this->evaluationService->find(Type::class, (int)$evaluationTypeId);
            $project = $this->projectService->findProjectById((int)$projectId);
            if (null === $country || null === $evaluationType || null === $project) {
                throw new InvalidArgumentException('The country, evaluationType or project cannot be null');
            }
            $resource = new Evaluation();
            $resource->setCountry($country);
            $resource->setType($evaluationType);
            $resource->setProject($project);
        }


        //Give no access when no access to the project itself
        if (!$this->projectAssertion->assert($acl, $role, $resource->getProject(), 'view-community')) {
            return false;
        }

        switch ($this->getPrivilege()) {
            case 'download-version-documents':
                return $this->hasContact();
            case 'edit-admin':
            case 'new-admin':
                return $this->rolesHaveAccess([Access::ACCESS_OFFICE]);
            case 'evaluate-project':
                //Office always has rights to edit a project
                if ($this->rolesHaveAccess([Access::ACCESS_OFFICE])) {
                    return true;
                }

                switch ($resource->getType()->getId()) {
                    case Type::TYPE_PO_EVALUATION:
                        /*
                         * Check first of the project has a correct version
                         */
                        $poClosedDate = $resource->getProject()->getCall()->getPoCloseDate();
                        if ($poClosedDate->add(new DateInterval('P4M')) < new DateTime()) {
                            return false;
                        }
                        break;
                    case Type::TYPE_FPP_EVALUATION:
                        $fppClosedDate = $resource->getProject()->getCall()->getFppCloseDate();
                        if ($fppClosedDate->add(new DateInterval('P4M')) < new DateTime()) {
                            return false;
                        }
                        break;
                    case Type::TYPE_FUNDING_STATUS:
                        /*
                         * Funding status is office only, the call cannot be open
                         */
                        break;
                }
                /*
                 * Check to see if we have an active version
                 */
                /** @var VersionType $versionType */
                $versionType = $this->versionService->find(VersionType::class, $resource->getType()->getVersionType());
                $version = $this->versionService->findLatestVersionByType(
                    $resource->getProject(),
                    $versionType
                );

                if (null === $version) {
                    return false;
                }
                /*
                 * Now return only true when the contact/country is participating in the project
                 */
                $contactActiveInCountry = false;

                $contactCountry = $this->contactService->parseCountry($this->contact);
                $countries = $this->countryService->findCountryByProject($resource->getProject());
                foreach ($countries as $country) {
                    if (!$contactActiveInCountry
                        && null !== $contactCountry
                        && $contactCountry->getId() === $country->getId()
                    ) {
                        $contactActiveInCountry = true;
                    }
                }
                /*
                 * When the contact is not active in the country, return false because we do not allow evaluation
                 */
                if (!$contactActiveInCountry) {
                    return false;
                }

                /*
                 * No errors found, return true
                 */

                return true;
            case 'overview-project':
            case 'download-project':
                if ($this->rolesHaveAccess([Access::ACCESS_OFFICE])) {
                    return true;
                }

                /*
                 * Now return only true when the contact/country is participating in the project
                 */
                $contactActiveInCountry = false;
                $contactCountry = $this->contactService->parseCountry($this->contact);

                $countries = $this->countryService->findCountryByProject($resource->getProject());
                foreach ($countries as $country) {
                    if (!$contactActiveInCountry
                        && null !== $contactCountry
                        && $contactCountry->getId() === $country->getId()
                    ) {
                        $contactActiveInCountry = true;
                    }
                }
                /*
                 * When the contact is not active in the country, return false because we do not allow evaluation
                 */
                if (!$contactActiveInCountry) {
                    return false;
                }

                /** @var VersionType $versionType */
                $versionType = $this->versionService->find(VersionType::class, $resource->getType()->getVersionType());

                $version = $this->versionService->findLatestVersionByType($resource->getProject(), $versionType);

                if (null === $version) {
                    return false;
                }
                break;
            default:
                throw new InvalidArgumentException(sprintf('Incorrect privilege (%s) requested', $privilege));
        }

        return false;
    }
}
