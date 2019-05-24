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

namespace Evaluation\Repository\Report\Criterion;

use Doctrine\ORM\QueryBuilder;
use Evaluation\Entity\Report\Criterion\Category;
use Evaluation\Repository\FilteredObjectRepository;
use Gedmo\Sortable\Entity\Repository\SortableRepository;
use function array_key_exists;
use function in_array;

/**
 * Class CategoryRepository
 * @package Evaluation\Repository\Report\Criterion
 */
final class CategoryRepository extends SortableRepository implements FilteredObjectRepository
{
    public function findFiltered(array $filter = []): QueryBuilder
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('project_entity_evaluation_report_criterion_category');
        $queryBuilder->from(
            Category::class,
            'project_entity_evaluation_report_criterion_category'
        );

        $direction = 'ASC';
        if (isset($filter['direction']) && in_array(strtoupper($filter['direction']), ['ASC', 'DESC'])) {
            $direction = strtoupper($filter['direction']);
        }

        // Filter on the name
        if (array_key_exists('search', $filter)) {
            $queryBuilder->andWhere($queryBuilder->expr()->like(
                'project_entity_evaluation_report_criterion_category.category',
                ':like'
            ));
            $queryBuilder->setParameter('like', sprintf("%%%s%%", $filter['search']));
        }

        switch ($filter['order']) {
            case 'id':
                $queryBuilder->addOrderBy('project_entity_evaluation_report_criterion_category.id', $direction);
                break;
            case 'category':
                $queryBuilder->addOrderBy('project_entity_evaluation_report_criterion_category.category', $direction);
                break;
            case 'confidential':
                $queryBuilder->addOrderBy('project_entity_evaluation_report_criterion_category.confidential', $direction);
                break;
            default:
                $queryBuilder->addOrderBy('project_entity_evaluation_report_criterion_category.sequence', $direction);
        }

        return $queryBuilder;
    }
}
