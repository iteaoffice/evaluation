<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Acl\Assertion;

use Admin\Entity\Access;
use Evaluation\Entity\Feedback;
use Evaluation\Service\EvaluationService;
use Interop\Container\ContainerInterface;
use Project\Acl\Assertion\Project;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

/**
 * Class Feedback
 *
 * @package Project\Acl\Assertion\Evaluation
 */
final class FeedbackAssertion extends AbstractAssertion
{
    /**
     * @var EvaluationService
     */
    private $evaluationService;
    /**
     * @var Project
     */
    private $projectAssertion;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->evaluationService = $container->get(EvaluationService::class);
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

        if (! $feedback instanceof Feedback && null !== $id) {
            $feedback = $this->evaluationService->find(Feedback::class, (int)$id);
        }

        if (! $this->hasContact() || null === $feedback) {
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
