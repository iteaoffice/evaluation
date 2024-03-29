<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Repository\Reviewer;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Evaluation\Entity\Reviewer\Contact;
use Evaluation\Repository\FilteredObjectRepository;
use Project\Entity\Project;

/**
 * Class Contact
 *
 * @package Project\Repository\Review
 */
/*final*/

class ContactRepository extends EntityRepository implements FilteredObjectRepository
{
    public function findFiltered(array $filter = []): QueryBuilder
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('rc');
        $queryBuilder->from(Contact::class, 'rc');

        $direction = 'ASC';
        if (isset($filter['direction']) && \in_array(strtoupper($filter['direction']), ['ASC', 'DESC'], true)) {
            $direction = strtoupper($filter['direction']);
        }

        switch ($filter['order']) {
            case 'id':
                $queryBuilder->addOrderBy('rc.id', $direction);
                break;
            case 'handle':
                $queryBuilder->addOrderBy('rc.handle', $direction);
                break;
            default:
                $queryBuilder->addOrderBy('rc.id', $direction);
        }

        return $queryBuilder;
    }

    /**
     * Find the contacts from active partners that should appear in the ignored reviewer list
     * This includes STG members from companies that share a parent with the project affiliation
     *
     * @param Project $project
     * @return Contact[]
     */
    public function findIgnoredReviewers(Project $project): array
    {
        $query = sprintf('SELECT DISTINCT
                prc.*
                FROM affiliation a
                INNER JOIN organisation o ON o.organisation_id = a.organisation_id               
                INNER JOIN contact_organisation co ON co.organisation_id = o.organisation_id
                LEFT JOIN organisation_parent_organisation opo ON opo.organisation_id = o.organisation_id
                LEFT JOIN organisation_parent_organisation child_opo ON (child_opo.parent_id = opo.parent_id AND child_opo.organisation_id <> o.organisation_id)
                LEFT JOIN contact_organisation child_co ON child_co.organisation_id = child_opo.organisation_id
                LEFT JOIN selection_contact sc ON (sc.contact_id = co.contact_id OR sc.contact_id = child_co.contact_id)
                INNER JOIN selection s ON s.selection_id = sc.selection_id
                LEFT JOIN project_review_contact prc ON prc.contact_id = sc.contact_id
                WHERE a.project_id = %d
                AND sc.selection_id IN (46, 47)
                AND a.date_end IS NULL 
                AND prc.review_contact_id IS NOT NULL', $project->getId());

        $resultSetMapping = new Query\ResultSetMappingBuilder($this->_em);
        $resultSetMapping->addRootEntityFromClassMetadata(Contact::class, 'prc');

        $nativeQuery = $this->_em->createNativeQuery($query, $resultSetMapping);

        return $nativeQuery->getResult();

//        $queryBuilder = $this->_em->createQueryBuilder();
//        $queryBuilder->select('prc')->distinct();
//        $queryBuilder->from(Affiliation::class, 'a');
//
//        $queryBuilder->innerJoin('prc.contact', 'contact');
//        $queryBuilder->innerJoin('contact.contactOrganisation', 'co');
//        $queryBuilder->innerJoin('co.organisation', 'o');
//        $queryBuilder->innerJoin('o.affiliation', 'a');
//        $queryBuilder->leftJoin('o.parentOrganisation', 'po');
//
//        $queryBuilder->leftJoin(
//            'o.parentOrganisation',
//            'child_po',
//            Query\Expr\Join::WITH,
//            $queryBuilder->expr()->andX(
//                $queryBuilder->expr()->eq('child_po.parent', 'po.parent'),
//                $queryBuilder->expr()->neq('child_po.organisation', 'o')
//            )
//        );
//        $queryBuilder->leftJoin(
//            'o.contactOrganisation',
//            'child_co',
//            Query\Expr\Join::WITH,
//            $queryBuilder->expr()->eq('child_co.organisation', 'child_po.organisation')
//        );
//        $queryBuilder->leftJoin(
//            'contact.selectionContact',
//            'sc',
//            Query\Expr\Join::WITH,
//            $queryBuilder->expr()->orX(
//                $queryBuilder->expr()->eq('sc.contact', 'co.contact'),
//                $queryBuilder->expr()->eq('sc.contact', 'child_co.contact')
//            )
//        );
//        $queryBuilder->innerJoin(
//            'contact.projectReviewerContact',
//            'prc_selection',
//            Query\Expr\Join::WITH,
//            $queryBuilder->expr()->eq('prc_selection.contact', 'sc.contact')
//        );
//        $queryBuilder->innerJoin('sc.selection', 's');
//        $queryBuilder->where($queryBuilder->expr()->eq('a.project', ':project'));
//        $queryBuilder->andWhere(
//            $queryBuilder->expr()->in('s.id', [Selection::SELECTION_STG, Selection::SELECTION_BSG])
//        );
//        $queryBuilder->andWhere($queryBuilder->expr()->isNull('a.dateEnd'));
//        $queryBuilder->andWhere($queryBuilder->expr()->isNotNull('prc.id'));
//
//        $queryBuilder->setParameter('project', $project);
//
//        return $queryBuilder->getQuery()->getResult();
    }
}
