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

namespace Evaluation\Acl\Assertion;

use Admin\Entity\Access;
use Interop\Container\ContainerInterface;
use Evaluation\Entity\Report as EvaluationReport;
use Project\Entity\Report\Reviewer as ReportReviewer;
use Project\Entity\Version\Reviewer as VersionReviewer;
use Evaluation\Service\EvaluationReportService;
use Project\Service\ProjectService;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use function count;

/**
 * Class ReportAssertion
 * @package Evaluation\Acl\Assertion
 */
final class ReportAssertion extends AbstractAssertion
{
    /**
     * @var EvaluationReportService
     */
    private $evaluationReportService;

    /**
     * @var ProjectService
     */
    private $projectService;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->evaluationReportService = $container->get(EvaluationReportService::class);
        $this->projectService          = $container->get(ProjectService::class);
    }

    public function assert(
        Acl $acl,
        RoleInterface $role = null,
        ResourceInterface $evaluationReport = null,
        $privilege = null
    ): bool {
        $this->setPrivilege($privilege);
        $contact = $this->contact;

        switch ($this->getPrivilege()) {
            case 'create':
            case 'new':
            case 'new-list':
            case 'overview':
            case 'download-offline-form':
                if ($this->rolesHaveAccess([Access::ACCESS_OFFICE])) {
                    return true;
                }

                $reportReviewId  = $this->getRouteMatch()->getParam('reportReviewer');
                $versionReviewId = $this->getRouteMatch()->getParam('versionReviewer');

                if ($reportReviewId) {
                    /** @var ReportReviewer $reportReview */
                    $reportReview = $this->projectService->find(ReportReviewer::class, (int)$reportReviewId);

                    return (($reportReview !== null) && ($reportReview->getContact()->getId() === $contact->getId()));
                }

                if ($versionReviewId) {
                    /** @var VersionReviewer $versionReview */
                    $versionReview = $this->projectService->find(VersionReviewer::class, (int)$versionReviewId);

                    return (($versionReview !== null) && ($versionReview->getContact()->getId() === $contact->getId()));
                }

                /**
                 * This might be a bit counter-intuitive but it makes sense. We have not found any id so it is used to
                 * display the link and the link shown is limited based on the access level. We can therefore assume that it is always true.
                 * If the person clicks the link there will be some id's and the system will then check if the user has access
                 */
                return true;

            case 'list':
                if ($this->rolesHaveAccess([Access::ACCESS_OFFICE])) {
                    return true;
                }

                $reportReviews  = $contact->getProjectReportReviewers();
                $versionReviews = $contact->getProjectVersionReviewers();

                return ((count($reportReviews) + count($versionReviews)) > 0);

            case 'view':
            case 'download':
            case 'download-distributable':
            case 'update':
            case 'edit':
            case 'finalise':
            case 'undo-final':
                if ($this->rolesHaveAccess([Access::ACCESS_OFFICE, ''])) {
                    return true;
                }

                // When no evaluation report is set, get it by ID from the route param
                if (! ($evaluationReport instanceof EvaluationReport)) {
                    $id = $this->getRouteMatch()->getParam('id');
                    if ($id === null) {
                        return false;
                    }
                    $evaluationReport = $this->evaluationReportService->find(EvaluationReport::class, (int)$id);
                    if ($evaluationReport === null) {
                        return false;
                    }
                }

                $reviewContact = null;
                /** @var EvaluationReport $evaluationReport */
                switch ($evaluationReport->getVersion()->getReportType()->getId()) {
                    case EvaluationReport\Type::TYPE_REPORT:
                        $reviewContact = $evaluationReport->getProjectReportReport()->getReviewer()->getContact();
                        break;
                    case EvaluationReport\Type::TYPE_PO_VERSION:
                    case EvaluationReport\Type::TYPE_FPP_VERSION:
                    case EvaluationReport\Type::TYPE_MAJOR_CR_VERSION:
                    case EvaluationReport\Type::TYPE_MINOR_CR_VERSION:
                        $reviewContact = $evaluationReport->getProjectVersionReport()->getReviewer()->getContact();
                        break;
                }

                return (($reviewContact !== null) && ($contact->getId() === $reviewContact->getId()));
            default:
                return $this->rolesHaveAccess([Access::ACCESS_OFFICE]);
        }
    }
}
