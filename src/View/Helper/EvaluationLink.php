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

namespace Evaluation\View\Helper;

use Evaluation\Acl\Assertion\EvaluationAssertion;
use Evaluation\Entity\Evaluation;
use Evaluation\Entity\Type;
use General\Entity\Country;
use Project\Entity\Project;

/**
 * Class EvaluationLink
 *
 * @package Evaluation\View\Helper
 */
final class EvaluationLink extends AbstractLink
{
    public function __invoke(
        Evaluation $evaluation = null,
        Project $project = null,
        Type $type = null,
        Country $country = null,
        string $action = 'evaluate-project',
        string $show = 'text',
        array $classes = []
    ): string {
        $this->reset();

        if (null === $evaluation) {
            $evaluation = new Evaluation();
            $evaluation->setProject($project ?? new Project());
            $evaluation->setType($type ?? new Type());
            $evaluation->setCountry($country ?? new Country());
        }

        if ($evaluation->getId()) {
            $this->addRouteParam('id', $evaluation->getId());
        }
        if (null !== $project) {
            $this->addRouteParam('project', $project->getId());
        }
        if (null !== $type) {
            $this->addRouteParam('type', $type->getId());
        }
        if (null !== $country) {
            $this->addRouteParam('country', $country->getId());
        }

        if (!$this->hasAccess($evaluation, EvaluationAssertion::class, $action)) {
            return '';
        }

        $this->addClasses($classes);

        $this->parseAction($action, $evaluation);

        return $this->createLink($show);
    }

    public function parseAction(string $action, Evaluation $evaluation): void
    {
        $this->action = $action;

        switch ($action) {
            case 'evaluate-project':
                //Evaluate and overview are the same actions
            case 'overview-project':
                $this->setLinkIcon('fa-list-ul');
                /*
                 * The parameters are the same but the router and the text change
                 */
                if ($action === 'overview-project') {
                    $this->setRoute('community/evaluation/overview-project');
                    $this->setText(
                        sprintf(
                            $this->translator->translate('txt-overview-%s-evaluation-for-project-%s-in-%s'),
                            $evaluation->getType(),
                            $evaluation->getProject(),
                            $evaluation->getCountry()
                        )
                    );
                } else {
                    $this->setRoute('community/evaluation/evaluate-project');
                    $this->setText(
                        sprintf(
                            $this->translator->translate('txt-give-%s-evaluation-for-project-%s-in-%s'),
                            $evaluation->getType(),
                            $evaluation->getProject(),
                            $evaluation->getCountry()
                        )
                    );
                }
                break;
            case 'download-project':
                $this->setLinkIcon('fa-file-pdf-o');
                $this->setRoute('community/evaluation/download-project');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-download-overview-%s-evaluation-for-project-%s-in-%s'),
                        $evaluation->getType(),
                        $evaluation->getProject(),
                        $evaluation->getCountry()
                    )
                );
                break;
            case 'edit-admin':
                $this->setRoute('zfcadmin/project/evaluation/edit');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-edit-%s-evaluation-for-project-%s-in-%s'),
                        $evaluation->getType(),
                        $evaluation->getProject(),
                        $evaluation->getCountry()
                    )
                );
                break;
            case 'new-admin':
                $this->setRoute('zfcadmin/project/evaluation/new');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-add-evaluation-for-project-%s'),
                        $evaluation->getProject()
                    )
                );
                break;
        }
    }
}
