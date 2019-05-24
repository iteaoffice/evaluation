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
use Interop\Container\ContainerInterface;
use Project\Acl\Assertion\Project;
use Evaluation\Entity\Feedback;
use Project\Service\ProjectService;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

/**
 * Class Feedback
 *
 * @package Project\Acl\Assertion\Evaluation
 */
final class FeedbackAssertion extends AbstractAssertion
{
    /**
     * @var ProjectService
     */
    private $projectService;
    /**
     * @var Project
     */
    private $projectAssertion;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->projectService = $container->get(ProjectService::class);
        $this->projectAssertion = $container->get(Project::class);
    }

    public function assert(
        Acl $acl,
        RoleInterface $role = null,
        ResourceInterface $feedback = null,
        $privilege = null
    ): bool {
        $this->setPrivilege($privilege);
        $id = $this->getId();

        if (!$feedback instanceof Feedback && null !== $id) {
            $feedback = $this->projectService->find(Feedback::class, (int)$id);
        }

        if (!$this->hasContact() || null === $feedback) {
            return false;
        }

        switch ($this->getPrivilege()) {
            case 'view':
                return $this->projectAssertion->assert(
                    $acl,
                    $role,
                    $feedback->getVersion()->getProject(),
                    'view-community'
                );
            case 'edit':
                return $this->projectAssertion->assert(
                    $acl,
                    $role,
                    $feedback->getVersion()->getProject(),
                    'edit-community'
                );
            case 'new':
            case 'view-admin':
            case 'edit-admin':
                return $this->rolesHaveAccess([Access::ACCESS_OFFICE]);
        }

        return false;
    }
}
