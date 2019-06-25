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

namespace Evaluation\Acl\Assertion;

use Admin\Entity\Access;
use Evaluation\Entity\Reviewer;
use Evaluation\Service\ReviewerService;
use Interop\Container\ContainerInterface;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

/**
 * Class ReviewerAssertion
 * @package Evaluation\Acl\Assertion
 */
final class ReviewerAssertion extends AbstractAssertion
{
    /**
     * @var ReviewerService
     */
    private $reviewerService;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->reviewerService = $container->get(ReviewerService::class);
    }

    public function assert(
        Acl               $acl,
        RoleInterface     $role = null,
        ResourceInterface $reviewer = null,
        $privilege = null
    ): bool {
        $this->setPrivilege($privilege);
        $id = $this->getId();

        if ((!$reviewer instanceof Reviewer) && (null !== $id)) {
            $reviewer = $this->reviewerService->find(Reviewer::class, (int)$id);
        }

        if (null === $reviewer) {
            return false;
        }

        switch ($this->getPrivilege()) {
            case 'list-contacts':
            case 'new':
            case 'edit':
            case 'delete':
            case 'export':
                return $this->rolesHaveAccess(Access::ACCESS_OFFICE);
            default:
                return false;
        }
    }
}
