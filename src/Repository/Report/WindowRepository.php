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

use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Sortable\Entity\Repository\SortableRepository;
use Evaluation\Entity\Report\Version;
use Evaluation\Entity\Report\Window;
use Evaluation\Repository\FilteredObjectRepository;

use function array_key_exists;
use function in_array;
use function strtoupper;

/**
 * Class WindowRepository
 * @package Evaluation\Repository\Report
 */
final class WindowRepository extends SortableRepository implements FilteredObjectRepository
{
    public function findFiltered(array $filter = []): QueryBuilder
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('w');
        $queryBuilder->from(Window::class, 'w');

        $direction = 'ASC';
        if (isset($filter['direction']) && in_array(strtoupper($filter['direction']), [Criteria::ASC, Criteria::DESC])) {
            $direction = strtoupper($filter['direction']);
        }

        // Filter on the title
        if (array_key_exists('search', $filter)) {
            $queryBuilder->andWhere($queryBuilder->expr()->like('w.title', ':like'));
            $queryBuilder->setParameter('like', sprintf("%%%s%%", $filter['search']));
        }

        switch ($filter['order']) {
            case 'id':
                $queryBuilder->addOrderBy('w.id', $direction);
                break;
            case 'title':
                $queryBuilder->addOrderBy('w.title', $direction);
                break;
            case 'report-start':
                $queryBuilder->addOrderBy('w.dateStartReport', $direction);
                break;
            case 'report-end':
                $queryBuilder->addOrderBy('w.dateEndReport', $direction);
                break;
            case 'selection-start':
                $queryBuilder->addOrderBy('w.dateStartSelection', $direction);
                break;
            case 'selection-end':
                $queryBuilder->addOrderBy('w.dateEndSelection', $direction);
                break;
            default:
                $queryBuilder->addOrderBy('w.id', $direction);
        }

        return $queryBuilder;
    }

    public function findActiveWindows(Version $reportVersion = null): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('w')->distinct();
        $queryBuilder->from(Window::class, 'w');
        $queryBuilder->where(
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->gte(':now', 'w.dateStartReport'),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->isNull('w.dateEndReport'),
                    $queryBuilder->expr()->lte(':now', 'w.dateEndReport')
                )
            )
        );
        if ($reportVersion instanceof Version) {
            $queryBuilder->innerJoin('w.reportVersions', 'rv');
            $queryBuilder->andWhere($queryBuilder->expr()->eq('rv.id', ':repportVersionId'));
            $queryBuilder->setParameter('repportVersionId', $reportVersion->getId());
        }
        $queryBuilder->setParameter('now', new DateTime(), Types::DATETIME_MUTABLE);

        return $queryBuilder->getQuery()->getResult();
    }
}
