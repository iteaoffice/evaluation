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
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Evaluation\Entity\Report\Result;
use Evaluation\Entity\Report\Type;
use Evaluation\Entity\Report\Version;
use Evaluation\Repository\FilteredObjectRepository;
use Project\Entity\Version\Version as ProjectVersion;
use function array_key_exists;
use function implode;
use function in_array;
use function reset;
use function strtoupper;

/**
 * Class VersionRepository
 *
 * @package Evaluation\Repository\Report
 */
/*final*/ class VersionRepository extends EntityRepository implements FilteredObjectRepository
{
    public function findFiltered(array $filter = []): QueryBuilder
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('v');
        $queryBuilder->from(Version::class, 'v');
        $queryBuilder->innerJoin('v.reportType', 'rt');

        $direction = Criteria::DESC;
        if (isset($filter['direction'])
            && in_array(strtoupper($filter['direction']), [Criteria::ASC, Criteria::DESC], true)
        ) {
            $direction = strtoupper($filter['direction']);
        }

        // Filter on the name
        if (array_key_exists('search', $filter)) {
            $queryBuilder->andWhere($queryBuilder->expr()->like('v.label', ':like'));
            $queryBuilder->setParameter('like', sprintf("%%%s%%", $filter['search']));
        }

        // Filter on the report type
        if (array_key_exists('type', $filter)) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('rt.id', implode(',', $filter['type'])));
        }

        // Filter on enabled
        if (array_key_exists('show', $filter) && ($filter['show'] !== 'all')) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('v.archived', ':archived'));
            $queryBuilder->setParameter('archived', ($filter['show'] === 'archived') ? 1 : 0);
        }

        switch ($filter['order']) {
            case 'id':
                $queryBuilder->addOrderBy('v.id', $direction);
                break;
            case 'label':
                $queryBuilder->addOrderBy('v.label', $direction);
                break;
            case 'type':
                $queryBuilder->addOrderBy('rt.type', $direction);
                break;
            case 'archived':
                $queryBuilder->addOrderBy('v.archived', $direction);
                break;
            case 'created':
                $queryBuilder->addOrderBy('v.dateCreated', $direction);
                break;
            default:
                $queryBuilder->addOrderBy('v.id', $direction);
        }

        return $queryBuilder;
    }

    public function findByProjectVersion(ProjectVersion $projectVersion): ?Version
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('v');
        $queryBuilder->from(Version::class, 'v');
        $queryBuilder->innerJoin('v.reportType', 'rt');
        $queryBuilder->where($queryBuilder->expr()->eq('rt.versionType', ':versionType'));
        $queryBuilder->andWhere($queryBuilder->expr()->eq('v.archived', ':archived'));
        $queryBuilder->orderBy('v.dateCreated', Criteria::DESC);
        $queryBuilder->setMaxResults(1);

        $queryBuilder->setParameter('versionType', $projectVersion->getVersionType());
        $queryBuilder->setParameter('archived', 0);

        $result = $queryBuilder->getQuery()->getResult();
        if (!empty($result)) {
            return reset($result);
        }

        return null;
    }

    public function findForProjectReport(): ?Version
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('v');
        $queryBuilder->from(Version::class, 'v');
        $queryBuilder->innerJoin('v.reportType', 'rt');
        $queryBuilder->where($queryBuilder->expr()->eq('rt.id', ':reportTypeId'));
        $queryBuilder->andWhere($queryBuilder->expr()->eq('v.archived', ':archived'));
        $queryBuilder->orderBy('v.dateCreated', Criteria::DESC);
        $queryBuilder->setMaxResults(1);

        $queryBuilder->setParameter('reportTypeId', Type::TYPE_REPORT);
        $queryBuilder->setParameter('archived', 0);

        $result = $queryBuilder->getQuery()->getResult();
        if (!empty($result)) {
            return reset($result);
        }

        return null;
    }
}
