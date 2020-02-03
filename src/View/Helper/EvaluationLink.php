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
use General\ValueObject\Link\Link;
use General\ValueObject\Link\LinkDecoration;
use General\View\Helper\AbstractLink;
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
        Type $evaluationType = null,
        Country $country = null,
        string $action = 'evaluate-project',
        string $show = LinkDecoration::SHOW_TEXT
    ): string {
        if (null === $evaluation) {
            $evaluation = new Evaluation();
            $evaluation->setProject($project ?? new Project());
            $evaluation->setType($evaluationType ?? new Type());
            $evaluation->setCountry($country ?? new Country());
        }

        if (!$this->hasAccess($evaluation, EvaluationAssertion::class, $action)) {
            return '';
        }

        $routeParams = [];
        if (!$evaluation->isEmpty()) {
            $routeParams['id'] = $evaluation->getId();
        }
        if (null !== $project) {
            $routeParams['project'] = $project->getId();
        }
        if (null !== $evaluationType) {
            $routeParams['type'] = $evaluationType->getId();
        }
        if (null !== $country) {
            $routeParams['country'] = $country->getId();
        }

        switch ($action) {
            case 'evaluate-project':
                // Evaluate and overview are the same actions
            case 'overview-project':
                // The parameters are the same but the router and the text change
                if ($action === 'overview-project') {
                    $linkParams = [
                        'icon' => 'fas fa-list',
                        'route' => 'community/evaluation/overview-project',
                        'text' => sprintf(
                            $this->translator->translate('txt-overview-%s-evaluation-for-project-%s-in-%s'),
                            $evaluation->getType(),
                            $evaluation->getProject(),
                            $evaluation->getCountry()
                        )
                    ];
                } else {
                    $linkParams = [
                        'icon' => 'fas fa-list',
                        'route' => 'community/evaluation/evaluate-project',
                        'text' => sprintf(
                            $this->translator->translate('txt-give-%s-evaluation-for-project-%s-in-%s'),
                            $evaluation->getType(),
                            $evaluation->getProject(),
                            $evaluation->getCountry()
                        )
                    ];
                }
                break;
            case 'download-project':
                $linkParams = [
                    'icon' => 'far fa-file-pdf',
                    'route' => 'community/evaluation/download-project',
                    'text' => sprintf(
                        $this->translator->translate('txt-download-overview-%s-evaluation-for-project-%s-in-%s'),
                        $evaluation->getType(),
                        $evaluation->getProject(),
                        $evaluation->getCountry()
                    )
                ];
                break;
            case 'edit-admin':
                $linkParams = [
                    'icon' => 'far fa-edit',
                    'route' => 'zfcadmin/project/evaluation/edit',
                    'text' => sprintf(
                        $this->translator->translate('txt-edit-%s-evaluation-for-project-%s-in-%s'),
                        $evaluation->getType(),
                        $evaluation->getProject(),
                        $evaluation->getCountry()
                    )
                ];
                break;
            case 'new-admin':
                $linkParams = [
                    'icon' => 'fas fa-plus',
                    'route' => 'zfcadmin/project/evaluation/new',
                    'text' => sprintf(
                        $this->translator->translate('txt-add-evaluation-for-project-%s'),
                        $evaluation->getProject()
                    )
                ];
                break;
            default:
                return '';
        }
        $linkParams['action'] = $action;
        $linkParams['show'] = $show;
        $linkParams['routeParams'] = $routeParams;

        return $this->parse(Link::fromArray($linkParams));
    }
}
