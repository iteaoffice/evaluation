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

namespace Evaluation\View\Helper;

use Contact\Entity\Contact;
use Evaluation\Entity\Evaluation;
use Evaluation\Entity\Type;
use General\Entity\Country;
use InvalidArgumentException;
use Project\Acl\Assertion\Evaluation\Evaluation as EvaluationAssertion;
use Project\Entity\Project;

/**
 * Class EvaluationLink
 * @package Evaluation\View\Helper
 */
final class EvaluationLink extends AbstractLink
{
    /**
     * @var Evaluation
     */
    private $evaluation;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var Type
     */
    private $type;

    /**
     * @var Country
     */
    private $country;

    public function __invoke(
        Evaluation $evaluation = null,
        Project    $project = null,
        Type       $evaluationType = null,
        Country    $country = null,
        string     $action = 'evaluate-project',
        string     $show = 'text',
        array      $classes = []
    ): string {
        $this->project    = $project ?? new Project();
        $this->type       = $type ?? new Type();
        $this->country    = $country ?? new Country();
        $this->evaluation = $evaluation ?? $this->initEvaluation();
        $this->setAction($action);
        $this->setShow($show);

        $this->classes = [];
        $this->addClasses($classes);

        $this->addRouterParam('id', $this->evaluation->getId());
        $this->addRouterParam('project', $this->project->getId());
        $this->addRouterParam('type', $this->type->getId());
        $this->addRouterParam('country', $this->country->getId());

        if (!$this->hasAccess($this->evaluation, EvaluationAssertion::class, $this->getAction())) {
            return '';
        }

        return $this->createLink();
    }

    /**
     * @return Evaluation
     */
    public function initEvaluation(): Evaluation
    {
        /*
         * $projectService, $evaluationType, $country cannot be null when we want to create a new evaluation
         */
        if ($this->project->isEmpty()) {
            throw new InvalidArgumentException(
                sprintf(
                    "Project cannot be null to give evaluation in %s",
                    __CLASS__
                )
            );
        }
        if ($this->type->isEmpty()) {
            throw new InvalidArgumentException(
                sprintf(
                    "Evaluation type cannot be null to give evaluation in %s",
                    __CLASS__
                )
            );
        }
        if ($this->country->isEmpty()) {
            throw new InvalidArgumentException(
                sprintf(
                    "The country cannot be null to give evaluation in %s",
                    __CLASS__
                )
            );
        }
        $this->evaluation = new Evaluation();
        $this->evaluation->setProject($this->project);
        /** @var Contact $contact */
        $contact = $this->getAuthorizeService()->getIdentity();
        $this->evaluation->setContact($contact);
        $this->evaluation->setType($this->type);
        $this->evaluation->setCountry($this->country);

        return $this->evaluation;
    }

    /**
     * Parse the action.
     */
    public function parseAction(): void
    {
        switch ($this->getAction()) {
            case 'evaluate-project':
                //Evaluate and overview are the same actions
            case 'overview-project':
                /*
                 * The parameters are the same but the router and the text change
                 */
                if ($this->getAction() === 'overview-project') {
                    $this->setRouter('community/evaluation/overview-project');
                    $this->setText(
                        sprintf(
                            $this->translator->translate("txt-overview-%s-evaluation-for-project-%s-in-%s"),
                            $this->type,
                            $this->project->parseFullName(),
                            $this->country
                        )
                    );
                } else {
                    $this->setRouter('community/evaluation/evaluate-project');
                    $this->setText(
                        sprintf(
                            $this->translator->translate("txt-give-%s-evaluation-for-project-%s-in-%s"),
                            $this->type,
                            $this->project->parseFullName(),
                            $this->country
                        )
                    );
                }
                break;
            case 'download-project':
                $this->setRouter('community/evaluation/download-project');
                $this->setText(
                    sprintf(
                        $this->translator->translate("txt-download-overview-%s-evaluation-for-project-%s-in-%s"),
                        $this->type,
                        $this->project->parseFullName(),
                        $this->country
                    )
                );
                break;
            case 'edit-admin':
                $this->setRouter('zfcadmin/project/evaluation/edit');
                $this->setText(
                    sprintf(
                        $this->translator->translate("txt-edit-%s-evaluation-for-project-%s-in-%s"),
                        $this->evaluation->getType(),
                        $this->evaluation->getProject(),
                        $this->evaluation->getCountry()
                    )
                );
                break;
            case 'new-admin':
                $this->setRouter('zfcadmin/project/evaluation/new');
                $this->setText(
                    sprintf(
                        $this->translator->translate("txt-add-evaluation-for-project-%s"),
                        $this->project->parseFullName()
                    )
                );
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf(
                        "%s is an incorrect action for %s",
                        $this->getAction(),
                        __CLASS__
                    )
                );
        }
    }
}
