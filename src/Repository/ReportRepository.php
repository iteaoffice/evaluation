<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Repository;

use Contact\Entity\Contact;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Entity\Report\Type as EvaluationReportType;
use Evaluation\Entity\Report\Version as EvaluationReportVersion;
use Evaluation\Service\EvaluationReportService;
use Program\Entity\Call\Call;
use Program\Repository\Call\Call as CallRepository;
use Project\Entity\ChangeRequest\Process;
use Project\Entity\Report\Report as ProjectReport;
use Project\Entity\Report\Reviewer as ReportReviewer;
use Project\Entity\Version\Reviewer as VersionReviewer;
use Project\Entity\Version\Type as VersionType;
use Project\Entity\Version\Version;

use function array_merge;
use function in_array;

/**
 * Can't be final because of unit test
 *
 * Class ReportRepository
 * @package Evaluation\Repository
 */
/*final*/

class ReportRepository extends EntityRepository implements FilteredObjectRepository
{
    public function findReviewReportsByContact(
        Contact $contact,
        int $status = EvaluationReportService::STATUS_NEW,
        bool $onlyActiveWindow = true
    ): array {
        $return = [];

        // Get the open time windows with reports in them
        $qbWindow = $this->_em->createQueryBuilder();
        $qbWindow->select('w', 'rv', 'rt');
        $qbWindow->from(EvaluationReport\Window::class, 'w');
        $qbWindow->innerJoin('w.reportVersions', 'rv');
        $qbWindow->innerJoin('rv.reportType', 'rt');
        $qbWindow->leftJoin('rt.versionType', 'vt');
        if ($onlyActiveWindow) {
            $qbWindow->where(
                $qbWindow->expr()->andX(
                    $qbWindow->expr()->gte(':now', 'w.dateStartReport'),
                    $qbWindow->expr()->orX(
                        $qbWindow->expr()->isNull('w.dateEndReport'),
                        $qbWindow->expr()->lte(':now', 'w.dateEndReport')
                    )
                )
            );
            $qbWindow->setParameter('now', new DateTime(), Types::DATETIME_MUTABLE);
        }
        $windows = $qbWindow->getQuery()->getResult();

        switch ($status) {
            case EvaluationReportService::STATUS_NEW:
                /** @var EvaluationReport\Window $window */
                foreach ($windows as $window) {
                    $return[$window->getId()] = [
                        'window'  => $window,
                        'reviews' => []
                    ];
                    $versionTypes             = [];
                    /** @var EvaluationReportVersion $reportVersion */
                    foreach ($window->getReportVersions() as $reportVersion) {
                        $versionType = $reportVersion->getReportType()->getVersionType();

                        // Window is for a version
                        if (($versionType !== null) && ! in_array($versionType->getId(), $versionTypes)) {
                            $qb1 = $this->_em->createQueryBuilder();
                            $qb1->select('vr');
                            $qb1->from(VersionReviewer::class, 'vr');
                            $qb1->innerJoin('vr.version', 'v');
                            $qb1->innerJoin('v.versionType', 'vt');
                            $qb1->leftJoin('vr.projectVersionReport', 'pvr');
                            $qb1->leftJoin('v.changerequestProcess', 'crp');
                            $qb1->where($qb1->expr()->eq('vr.contact', ':contact'));
                            $qb1->andWhere($qb1->expr()->isNull('pvr.id'));
                            $qb1->andWhere($qb1->expr()->eq('v.versionType', ':versionType'));
                            $qb1->andWhere($qb1->expr()->gte('v.dateSubmitted', ':dateStartSelection'));
                            $qb1->andWhere($qb1->expr()->isNull('v.dateReviewed'));
                            $qb1->andWhere($qb1->expr()->orX(
                                $qb1->expr()->isNull('crp.id'),
                                $qb1->expr()->eq('crp.type', ':processType')
                            ));
                            if ($window->getDateEndSelection() !== null) {
                                $qb1->andWhere($qb1->expr()->lt('v.dateSubmitted', ':dateEndSelection'));
                                $qb1->setParameter('dateEndSelection', $window->getDateEndSelection(), Types::DATETIME_MUTABLE);
                            }

                            $qb1->setParameter('contact', $contact);
                            $qb1->setParameter('versionType', $versionType);
                            $qb1->setParameter('dateStartSelection', $window->getDateStartSelection(), Types::DATETIME_MUTABLE);
                            $qb1->setParameter('processType', $reportVersion->getReportType()->getProcessType());

                            $return[$window->getId()]['reviews'] = array_merge(
                                $return[$window->getId()]['reviews'],
                                $qb1->getQuery()->useQueryCache(true)->getResult()
                            );
                            $versionTypes[]                      = $versionType->getId();
                        } // Window is for a PPR
                        elseif ($reportVersion->getReportType()->getId() === EvaluationReportType::TYPE_REPORT) {
                            $qb2 = $this->_em->createQueryBuilder();
                            $qb2->select('rr');
                            $qb2->from(ReportReviewer::class, 'rr');
                            $qb2->innerJoin('rr.projectReport', 'pr');
                            $qb2->leftJoin('rr.projectReportReport', 'prr');
                            $qb2->where($qb2->expr()->eq('rr.contact', ':contact'));
                            $qb2->andWhere($qb2->expr()->isNull('prr.id'));
                            $qb2->andWhere($qb2->expr()->gte('pr.dateFinal', ':dateStartSelection'));
                            if ($window->getDateEndSelection() !== null) {
                                $qb2->andWhere($qb2->expr()->lt('pr.dateFinal', ':dateEndSelection'));
                                $qb2->setParameter('dateEndSelection', $window->getDateEndSelection(), Types::DATETIME_MUTABLE);
                            }

                            $qb2->setParameter('contact', $contact);
                            $qb2->setParameter('dateStartSelection', $window->getDateStartSelection(), Types::DATETIME_MUTABLE);

                            $return[$window->getId()]['reviews'] = array_merge(
                                $return[$window->getId()]['reviews'],
                                $qb2->getQuery()->useQueryCache(true)->getResult()
                            );
                        }
                    }
                }
                break;

            case EvaluationReportService::STATUS_IN_PROGRESS:
                /** @var EvaluationReport\Window $window */
                foreach ($windows as $window) {
                    $return[$window->getId()] = [
                        'window'  => $window,
                        'reviews' => []
                    ];
                    $versionTypes             = [];
                    /** @var EvaluationReportVersion $reportVersion */
                    foreach ($window->getReportVersions() as $reportVersion) {
                        $versionType = $reportVersion->getReportType()->getVersionType();

                        // Window is for a version
                        if (($versionType !== null) && ! in_array($versionType->getId(), $versionTypes)) {
                            $qb1 = $this->_em->createQueryBuilder();
                            $qb1->select('er');
                            $qb1->from(EvaluationReport::class, 'er');
                            $qb1->innerJoin('er.projectVersionReport', 'pvr');
                            $qb1->innerJoin('pvr.reviewer', 'vr');
                            $qb1->innerJoin('vr.version', 'v');
                            $qb1->innerJoin('v.versionType', 'vt');
                            $qb1->where($qb1->expr()->eq('vr.contact', ':contact'));
                            $qb1->andWhere($qb1->expr()->eq('er.final', ':final'));
                            $qb1->andWhere($qb1->expr()->eq('v.versionType', ':versionType'));
                            $qb1->andWhere($qb1->expr()->gte('v.dateSubmitted', ':dateStartSelection'));
                            $qb1->andWhere($qb1->expr()->isNull('v.dateReviewed'));
                            if ($window->getDateEndSelection() !== null) {
                                $qb1->andWhere($qb1->expr()->lt('v.dateSubmitted', ':dateEndSelection'));
                                $qb1->setParameter('dateEndSelection', $window->getDateEndSelection(), Types::DATETIME_MUTABLE);
                            }

                            $qb1->setParameter('contact', $contact);
                            $qb1->setParameter('final', false);
                            $qb1->setParameter('versionType', $versionType);
                            $qb1->setParameter('dateStartSelection', $window->getDateStartSelection(), Types::DATETIME_MUTABLE);

                            $return[$window->getId()]['reviews'] = $qb1->getQuery()->useQueryCache(true)->getResult();
                            $versionTypes[]                      = $versionType->getId();
                        } // Window is for a PPR
                        elseif ($reportVersion->getReportType()->getId() === EvaluationReportType::TYPE_REPORT) {
                            $qb2 = $this->_em->createQueryBuilder();
                            $qb2->select('er');
                            $qb2->from(EvaluationReport::class, 'er');
                            $qb2->innerJoin('er.projectReportReport', 'prr');
                            $qb2->innerJoin('prr.reviewer', 'rr');
                            $qb2->innerJoin('rr.projectReport', 'pr');
                            $qb2->where($qb2->expr()->eq('rr.contact', ':contact'));
                            $qb2->andWhere($qb2->expr()->eq('er.final', ':final'));
                            $qb2->andWhere($qb2->expr()->gte('pr.dateFinal', ':dateStartSelection'));
                            if ($window->getDateEndSelection() !== null) {
                                $qb2->andWhere($qb2->expr()->lt('pr.dateFinal', ':dateEndSelection'));
                                $qb2->setParameter('dateEndSelection', $window->getDateEndSelection(), Types::DATETIME_MUTABLE);
                            }

                            $qb2->setParameter('contact', $contact);
                            $qb2->setParameter('final', false);
                            $qb2->setParameter('dateStartSelection', $window->getDateStartSelection(), Types::DATETIME_MUTABLE);

                            $return[$window->getId()]['reviews'] = $qb2->getQuery()->useQueryCache(true)->getResult();
                        }
                    }
                }
                break;

            case EvaluationReportService::STATUS_FINAL:
                $qb = $this->_em->createQueryBuilder();
                $qb->select('er');
                $qb->from(EvaluationReport::class, 'er');
                $qb->leftJoin('er.projectVersionReport', 'pvr');
                $qb->leftJoin('pvr.reviewer', 'vr');
                $qb->leftJoin('er.projectReportReport', 'prr');
                $qb->leftJoin('prr.reviewer', 'rr');
                $qb->where($qb->expr()->orX(
                    $qb->expr()->eq('vr.contact', ':contact'),
                    $qb->expr()->eq('rr.contact', ':contact')
                ));
                $qb->andWhere($qb->expr()->eq('er.final', ':final'));
                $qb->orderBy('er.dateUpdated', Criteria::DESC);

                $qb->setParameter('contact', $contact);
                $qb->setParameter('final', true);

                return $qb->getQuery()->useQueryCache(true)->getResult();

            default:
                return $return;
        }

        return $return;
    }

    public function findFiltered(array $filter = []): QueryBuilder
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        /** @var CallRepository $callRepository */
        $callRepository = $this->_em->getRepository(Call::class);
        $call           = null;
        if (isset($filter['call']) && ! empty($filter['call'])) {
            $call = $callRepository->find($filter['call']);
        }

        $finalReport = (array_key_exists('type', $filter) && ($filter['type'] === EvaluationReport::TYPE_FINAL));

        // Progress report review evaluation
        if (
            array_key_exists('subject', $filter)
            && ($filter['subject'] === (string)EvaluationReportType::TYPE_REPORT)
        ) {
            // Final evaluation report
            if ($finalReport) {
                $queryBuilder->select('pr', 'prr');
                $queryBuilder->from(ProjectReport::class, 'pr');
                $queryBuilder->innerJoin('pr.project', 'p');
                $queryBuilder->innerJoin('pr.reviewers', 'rr');
                $queryBuilder->leftJoin('pr.projectReportReport', 'prr');
                $queryBuilder->leftJoin('prr.evaluationReport', 'er');
                $queryBuilder->groupBy('pr.id');
                $queryBuilder->addGroupBy('prr.id');
            } // Individual evaluation report
            else {
                $queryBuilder->select('rr');
                $queryBuilder->from(ReportReviewer::class, 'rr');
                $queryBuilder->innerJoin('rr.contact', 'c');
                $queryBuilder->innerJoin('rr.projectReport', 'pr');
                $queryBuilder->innerJoin('pr.project', 'p');
                $queryBuilder->leftJoin('rr.projectReportReport', 'prr');
                $queryBuilder->leftJoin('prr.evaluationReport', 'er');
            }

            // Apply call filter
            if (null !== $call) {
                $queryBuilder->where($queryBuilder->expr()->eq('p.call', ':call'));
                $queryBuilder->setParameter('call', $call);
            }

            // Add year filter when present
            if (array_key_exists('year', $filter) && ! empty($filter['year'])) {
                $queryBuilder->andWhere($queryBuilder->expr()->eq('pr.year', ':year'));
                $queryBuilder->setParameter('year', $filter['year']);
            }

            // Add period filter when present
            if (array_key_exists('period', $filter) && ! empty($filter['period'])) {
                $queryBuilder->andWhere($queryBuilder->expr()->eq('pr.semester', ':semester'));
                $queryBuilder->setParameter('semester', $filter['period']);
            }

            // Add status filter when present
            if (array_key_exists('status', $filter) && ((int)$filter['status'] > 0)) {
                switch ((int)$filter['status']) {
                    case EvaluationReportService::STATUS_NEW:
                        $queryBuilder->andWhere($queryBuilder->expr()->isNull('prr.id'));
                        break;
                    case EvaluationReportService::STATUS_IN_PROGRESS:
                        $queryBuilder->andWhere($queryBuilder->expr()->eq('er.final', ':final'));
                        $queryBuilder->setParameter('final', 0);
                        break;
                    case EvaluationReportService::STATUS_FINAL:
                        $queryBuilder->andWhere($queryBuilder->expr()->eq('er.final', ':final'));
                        $queryBuilder->setParameter('final', 1);
                        break;
                }
            }

            // Set the sorting
            if (isset($filter['order'])) {
                $direction = strtoupper($filter['direction']);
                switch ($filter['order']) {
                    case 'project':
                        $queryBuilder->orderBy('p.project', $direction);
                        $queryBuilder->addOrderBy('pr.id', Criteria::ASC);
                        break;
                    case 'reviewer':
                        $queryBuilder->orderBy('c.firstName', $direction);
                        $queryBuilder->addOrderBy('c.middleName', $direction);
                        $queryBuilder->addOrderBy('c.lastName', $direction);
                        break;
                    case 'final':
                        $queryBuilder->orderBy('er.final', $direction);
                        break;
                    case 'report':
                        $queryBuilder->orderBy('pr.datePeriod', $direction);
                        break;
                    case 'created':
                        $queryBuilder->orderBy('prr.dateCreated', $direction);
                        break;
                    case 'updated':
                        $queryBuilder->orderBy('prr.dateUpdated', $direction);
                        break;
                    default:
                        $queryBuilder->orderBy('prr.dateUpdated', Criteria::DESC);
                }
            } else {
                $queryBuilder->orderBy('prr.dateUpdated', Criteria::DESC);
            }
        } // Project version review evaluation
        else {
            // Final evaluation report
            if ($finalReport) {
                $queryBuilder->select('v', 'vrr');
                $queryBuilder->from(Version::class, 'v');
                $queryBuilder->innerJoin('v.versionType', 'vt');
                $queryBuilder->innerJoin('v.project', 'p');
                $queryBuilder->innerJoin('v.reviewers', 'vr');
                $queryBuilder->leftJoin('v.projectVersionReport', 'vrr');
                $queryBuilder->leftJoin('vrr.evaluationReport', 'rr');
                $queryBuilder->groupBy('v.id');
                $queryBuilder->addGroupBy('vrr.id');
            } // Individual evaluation report
            else {
                $queryBuilder->select('vr');
                $queryBuilder->from(VersionReviewer::class, 'vr');
                $queryBuilder->innerJoin('vr.contact', 'c');
                $queryBuilder->innerJoin('vr.version', 'v');
                $queryBuilder->innerJoin('v.versionType', 'vt');
                $queryBuilder->innerJoin('v.project', 'p');
                $queryBuilder->leftJoin('vr.projectVersionReport', 'vrr');
                $queryBuilder->leftJoin('vrr.evaluationReport', 'rr');
            }

            // Apply call filter
            if (null !== $call) {
                $queryBuilder->where($queryBuilder->expr()->eq('p.call', ':call'));
                $queryBuilder->setParameter('call', $call);
            }

            // Add version type filter when present
            if (array_key_exists('subject', $filter) && ! empty($filter['subject'])) {
                $reportTypeSelect = $this->_em->createQueryBuilder();
                $reportTypeSelect->select('rt')
                    ->from(EvaluationReportType::class, 'rt')
                    ->where($reportTypeSelect->expr()->eq('rt.id', ':id'));
                $reportTypeSelect->setParameter('id', $filter['subject']);
                /** @var EvaluationReportType $reportType */
                $reportType = $reportTypeSelect->getQuery()->getSingleResult();

                /** @var EntityRepository $repository */
                $queryBuilder->andWhere($queryBuilder->expr()->eq('v.versionType', ':type'));
                $queryBuilder->setParameter('type', $reportType->getVersionType());

                // Add impact filter for CR when present
                if ($reportType->getVersionType()->getId() === VersionType::TYPE_CR) {
                    $queryBuilder->innerJoin('v.changerequestProcess', 'cp');
                    $queryBuilder->andWhere($queryBuilder->expr()->eq('cp.type', ':impact'));
                    $impact = ($reportType->getId() === EvaluationReportType::TYPE_MAJOR_CR_VERSION)
                        ? Process::TYPE_MAJOR
                        : Process::TYPE_MINOR;
                    $queryBuilder->setParameter('impact', $impact);
                }
            }

            // Add status filter when present
            if (array_key_exists('status', $filter) && ((int)$filter['status'] > 0)) {
                switch ((int)$filter['status']) {
                    case EvaluationReportService::STATUS_NEW:
                        $queryBuilder->andWhere($queryBuilder->expr()->isNull('vrr.id'));
                        break;
                    case EvaluationReportService::STATUS_IN_PROGRESS:
                        $queryBuilder->andWhere($queryBuilder->expr()->eq('rr.final', ':final'));
                        $queryBuilder->setParameter('final', 0);
                        break;
                    case EvaluationReportService::STATUS_FINAL:
                        $queryBuilder->andWhere($queryBuilder->expr()->eq('rr.final', ':final'));
                        $queryBuilder->setParameter('final', 1);
                        break;
                }
            }

            // Set the sorting
            if (isset($filter['order'])) {
                $direction = strtoupper($filter['direction']);
                switch ($filter['order']) {
                    case 'project':
                        $queryBuilder->orderBy('p.project', $direction);
                        $queryBuilder->addOrderBy('vt.id', Criteria::ASC);
                        break;
                    case 'reviewer':
                        $queryBuilder->orderBy('c.firstName', $direction);
                        $queryBuilder->addOrderBy('c.middleName', $direction);
                        $queryBuilder->addOrderBy('c.lastName', $direction);
                        break;
                    case 'final':
                        $queryBuilder->orderBy('rr.final', $direction);
                        break;
                    case 'version':
                        $queryBuilder->orderBy('vt.description', $direction);
                        break;
                    case 'created':
                        $queryBuilder->orderBy('vrr.dateCreated', $direction);
                        break;
                    case 'updated':
                        $queryBuilder->orderBy('vrr.dateUpdated', $direction);
                        break;
                    default:
                        $queryBuilder->orderBy('vrr.dateUpdated', Criteria::DESC);
                }
            } else {
                $queryBuilder->orderBy('vrr.dateUpdated', Criteria::DESC);
            }
        }

        // Add search when present
        if (array_key_exists('search', $filter) && ! empty($filter['search'])) {
            $match      = sprintf("%%%s%%", $filter['search']);
            $expression = $queryBuilder->expr()->like('p.project', ':project');

            if (! $finalReport) {
                $contactName = $queryBuilder->expr()->concat(
                    'c.firstName',
                    $queryBuilder->expr()->concat($queryBuilder->expr()->literal(' '), 'c.lastName')
                );
                $expression  = $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('p.project', ':project'),
                    $queryBuilder->expr()->like($contactName, ':contact')
                );
                $queryBuilder->setParameter('contact', $match);
            }

            $queryBuilder->andWhere($expression);
            $queryBuilder->setParameter('project', $match);
        }

        return $queryBuilder;
    }

    public function getSortedResults(EvaluationReport $evaluationReport): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('rr', 'cv', 'c', 'ct', 'cc');
        $queryBuilder->from(EvaluationReport\Result::class, 'rr');
        $queryBuilder->innerJoin('rr.criterionVersion', 'cv');
        $queryBuilder->innerJoin('cv.criterion', 'c');
        $queryBuilder->innerJoin('cv.type', 'ct');
        $queryBuilder->innerJoin('ct.category', 'cc');
        $queryBuilder->where($queryBuilder->expr()->eq('rr.evaluationReport', ':report'));

        $queryBuilder->orderBy('cc.sequence', Criteria::ASC);
        $queryBuilder->addOrderBy('ct.sequence', Criteria::ASC);
        $queryBuilder->addOrderBy('cv.sequence', Criteria::ASC);

        $queryBuilder->setParameter('report', $evaluationReport);

        return $queryBuilder->getQuery()->getResult();
    }

    public function getSortedCriterionVersions(EvaluationReportVersion $reportVersion): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('cv', 'c', 'ct', 'cc', 'cvt', 't');
        $queryBuilder->from(EvaluationReport\Criterion\Version::class, 'cv');
        $queryBuilder->innerJoin('cv.criterion', 'c');
        $queryBuilder->innerJoin('cv.type', 'ct');
        $queryBuilder->innerJoin('ct.category', 'cc');
        $queryBuilder->leftJoin('cv.versionTopics', 'cvt');
        $queryBuilder->leftJoin('cvt.topic', 't');
        $queryBuilder->where($queryBuilder->expr()->eq('cv.reportVersion', ':reportVersion'));
        $queryBuilder->andWhere($queryBuilder->expr()->eq('c.archived', 0));

        $queryBuilder->orderBy('cc.sequence', Criteria::ASC);
        $queryBuilder->addOrderBy('ct.sequence', Criteria::ASC);
        $queryBuilder->addOrderBy('cv.sequence', Criteria::ASC);

        $queryBuilder->setParameter('reportVersion', $reportVersion);

        return $queryBuilder->getQuery()->getResult();
    }
}
