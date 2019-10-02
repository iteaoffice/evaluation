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

namespace Evaluation\Repository\Report\Criterion;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Evaluation\Repository\FilteredObjectRepository;
use Gedmo\Sortable\Entity\Repository\SortableRepository;
use Evaluation\Entity\Report\Criterion\Type;

/**
 * Class TypeRepository
 * @package Evaluation\Repository\Report\Criterion
 */
final class TypeRepository extends SortableRepository implements FilteredObjectRepository
{
    public function findFiltered(array $filter = []): QueryBuilder
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('t', 'c');
        $queryBuilder->from(Type::class, 't');
        $queryBuilder->innerJoin('t.category', 'c');

        $direction = Criteria::ASC;
        if (isset($filter['direction'])
            && \in_array(\strtoupper($filter['direction']), [Criteria::ASC, Criteria::DESC])
        ) {
            $direction = \strtoupper($filter['direction']);
        }

        // Filter on the name
        if (\array_key_exists('search', $filter)) {
            $queryBuilder->andWhere($queryBuilder->expr()->like('t.type', ':like'));
            $queryBuilder->setParameter('like', sprintf("%%%s%%", $filter['search']));
        }

        // Filter on category
        if (\array_key_exists('category', $filter)) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('c.id', \implode(',', $filter['category'])));
        }

        switch ($filter['order']) {
            case 'id':
                $queryBuilder->addOrderBy('t.id', $direction);
                break;
            case 'type':
                $queryBuilder->addOrderBy('t.type', $direction);
                break;
            case 'category':
                $queryBuilder->addOrderBy('c.category', $direction);
                break;
            default:
                $queryBuilder->addOrderBy('t.sequence', $direction);
        }

        return $queryBuilder;
    }
}
