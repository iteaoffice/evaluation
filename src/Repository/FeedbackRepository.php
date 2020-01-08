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

namespace Evaluation\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Evaluation\Entity\Feedback;
use Project\Repository\FilteredObjectRepository;

/**
 * Class FeedbackRepository
 *
 * @package Evaluation\Repository
 */
final class FeedbackRepository extends EntityRepository implements FilteredObjectRepository
{
    public function findFiltered(array $filter = []): QueryBuilder
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('evaluation_entity_feedback');
        $queryBuilder->from(Feedback::class, 'evaluation_entity_feedback');

        $direction = 'DESC';
        if (
            isset($filter['direction'])
            && \in_array(strtoupper($filter['direction']), ['ASC', 'DESC'])
        ) {
            $direction = strtoupper($filter['direction']);
        }

        switch ($filter['order']) {
            case 'id':
                $queryBuilder->addOrderBy('evaluation_entity_feedback.id', $direction);
                break;
            case 'status':
                $queryBuilder->join('evaluation_entity_feedback.status', 'evaluation_entity_status');
                $queryBuilder->addOrderBy('evaluation_entity_status.status', $direction);
                break;
            case 'project':
                $queryBuilder->join('evaluation_entity_feedback.version', 'project_entity_version_version');
                $queryBuilder->join('project_entity_version_version.project', 'project_entity_project');
                $queryBuilder->addOrderBy('project_entity_project.project', $direction);
                break;
            case 'version-type':
                $queryBuilder->join('evaluation_entity_feedback.version', 'project_entity_version_version');
                $queryBuilder->join('project_entity_version_version.type', 'project_entity_version_type');
                $queryBuilder->addOrderBy('project_entity_version_type.type', $direction);
                break;
            case 'last-update':
                $queryBuilder->addOrderBy('evaluation_entity_feedback.dateUpdated', $direction);
                break;
            default:
                $queryBuilder->addOrderBy('evaluation_entity_feedback.dateCreated', $direction);
        }

        return $queryBuilder;
    }
}
