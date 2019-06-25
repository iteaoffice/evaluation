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

namespace Evaluation\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Evaluation\Entity\Reviewer;

final class ReviewerRepository extends EntityRepository
{
    public function findReviewContactByProjectQueryBuilder(): QueryBuilder
    {
        $limitQueryBuilder = $this->_em->createQueryBuilder();
        $limitQueryBuilder->select('c');
        $limitQueryBuilder->from(Reviewer::class, 'r');
        $limitQueryBuilder->join('pr.contact', 'c');
        $limitQueryBuilder->andWhere('r.project = :project');

        return $limitQueryBuilder;
    }
}
