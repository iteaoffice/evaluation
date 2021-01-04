<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Repository\Report\Criterion;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Evaluation\Repository\FilteredObjectRepository;
use Gedmo\Sortable\Entity\Repository\SortableRepository;
use Evaluation\Entity\Report\Criterion\Topic;

use function array_key_exists;
use function in_array;
use function strtoupper;

/**
 * Class TopicRepository
 * @package Evaluation\Repository\Report\Criterion
 */
final class TopicRepository extends SortableRepository implements FilteredObjectRepository
{
    public function findFiltered(array $filter = []): QueryBuilder
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('t');
        $queryBuilder->from(Topic::class, 't');

        $direction = 'ASC';
        if (isset($filter['direction']) && in_array(strtoupper($filter['direction']), [Criteria::ASC, Criteria::DESC], true)) {
            $direction = strtoupper($filter['direction']);
        }

        // Filter on the name
        if (array_key_exists('search', $filter)) {
            $queryBuilder->andWhere($queryBuilder->expr()->like('topic.topic', ':like'));
            $queryBuilder->setParameter('like', sprintf("%%%s%%", $filter['search']));
        }

        switch ($filter['order']) {
            case 'id':
                $queryBuilder->addOrderBy('t.id', $direction);
                break;
            case 'topic':
                $queryBuilder->addOrderBy('t.topic', $direction);
                break;
            default:
                $queryBuilder->addOrderBy('t.sequence', $direction);
        }

        return $queryBuilder;
    }
}
