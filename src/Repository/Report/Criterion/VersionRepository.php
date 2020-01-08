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
use Gedmo\Sortable\Entity\Repository\SortableRepository;
use Evaluation\Entity\Report\Criterion\Version as CriterionVersion;
use Evaluation\Entity\Report\Version;

/**
 * Class VersionRepository
 * @package Evaluation\Repository\Report\Criterion
 */
/*final*/ class VersionRepository extends SortableRepository
{
    public function findSorted(Version $reportVersion)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('crv', 'cr', 't', 'ca');
        $queryBuilder->from(CriterionVersion::class, 'crv');
        $queryBuilder->innerJoin('crv.criterion', 'cr');
        $queryBuilder->innerJoin('crv.type', 't');
        $queryBuilder->innerJoin('t.category', 'ca');
        $queryBuilder->where($queryBuilder->expr()->eq('crv.reportVersion', ':reportVersion'));

        $queryBuilder->orderBy('ca.sequence', Criteria::ASC);
        $queryBuilder->addOrderBy('t.sequence', Criteria::ASC);
        $queryBuilder->addOrderBy('crv.sequence', Criteria::ASC);

        $queryBuilder->setParameter('reportVersion', $reportVersion);

        return $queryBuilder->getQuery()->getResult();
    }
}
