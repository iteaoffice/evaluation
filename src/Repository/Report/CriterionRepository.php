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

namespace Evaluation\Repository\Report;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Sortable\Entity\Repository\SortableRepository;
use Evaluation\Entity\Report\Criterion;
use Evaluation\Entity\Report\Criterion\Version as CriterionVersion;
use Evaluation\Entity\Report\Type;
use Evaluation\Entity\Report\Version as ReportVersion;
use Evaluation\Repository\FilteredObjectRepository;

use function array_key_exists;
use function implode;
use function in_array;
use function strtoupper;

/**
 * Class CriterionRepository
 * @package Evaluation\Repository\Report
 */
final class CriterionRepository extends SortableRepository implements FilteredObjectRepository
{
    public function findFiltered(array $filter = []): QueryBuilder
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('cr', 'rt');
        $queryBuilder->from(Criterion::class, 'cr');
        $queryBuilder->innerJoin('cr.reportTypes', 'rt');

        $direction = Criteria::ASC;
        if (
            isset($filter['direction'])
            && in_array(strtoupper($filter['direction']), [Criteria::ASC, Criteria::DESC], true)
        ) {
            $direction = strtoupper($filter['direction']);
        }

        // Filter on the name
        if (array_key_exists('search', $filter)) {
            $queryBuilder->andWhere($queryBuilder->expr()->like('cr.criterion', ':like'));
            $queryBuilder->setParameter('like', sprintf("%%%s%%", $filter['search']));
        }

        // Filter on the report type
        if (array_key_exists('type', $filter)) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('rt.id', implode($filter['type'], ', ')));
        }

        // Filter on enabled
        if (array_key_exists('show', $filter) && ($filter['show'] !== 'all')) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('cr.archived', ':archived'));
            $queryBuilder->setParameter('archived', ($filter['show'] === 'archived') ? 1 : 0);
        } elseif (! array_key_exists('show', $filter)) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('cr.archived', ':archived'));
            $queryBuilder->setParameter('archived', 0);
        }

        // Filter on has score
        if (array_key_exists('has-score', $filter) && ($filter['has-score'] !== 'all')) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('cr.hasScore', ':hasScore'));
            $queryBuilder->setParameter('hasScore', ($filter['has-score'] === 'yes') ? 1 : 0);
        }

        switch ($filter['order']) {
            case 'id':
                $queryBuilder->addOrderBy('cr.id', $direction);
                break;
            case 'criterion':
                $queryBuilder->addOrderBy('cr.criterion', $direction);
                break;
            case 'has-score':
                $queryBuilder->addOrderBy('cr.hasScore', $direction);
                break;
            case 'archived':
                $queryBuilder->addOrderBy('cr.archived', $direction);
                break;
            default:
                $queryBuilder->addOrderBy('cr.sequence', $direction);
        }

        return $queryBuilder;
    }

    public function findForVersion(CriterionVersion $criterionVersion): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('cr');
        $queryBuilder->from(Criterion::class, 'cr');
        $queryBuilder->innerJoin('cr.reportTypes', 'rt');
        $queryBuilder->where(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('cr.archived', ':archived'),
                $queryBuilder->expr()->eq('cr.id', ':currentCriterionId')
            )
        );
        $queryBuilder->andWhere($queryBuilder->expr()->eq('rt.id', ':reportTypeId'));
        $queryBuilder->orderBy('cr.sequence', Criteria::ASC);

        $queryBuilder->setParameter('archived', 0);
        $currentCriterion = $criterionVersion->getCriterion() ?? new Criterion();
        $queryBuilder->setParameter('currentCriterionId', $currentCriterion->getId());
        $reportVersion = $criterionVersion->getReportVersion() ?? new ReportVersion();
        $reportType    = $reportVersion->getReportType() ?? new Type();
        $queryBuilder->setParameter('reportTypeId', $reportType->getId());

        return $queryBuilder->getQuery()->getResult();
    }
}
