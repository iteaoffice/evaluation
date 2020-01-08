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

namespace Evaluation\Navigation\Invokable;

use Admin\Navigation\Invokable\AbstractNavigationInvokable;
use Program\Entity\Call\Call;
use Project\Entity\Project;
use Laminas\Navigation\Page\Mvc;

/**
 * Class EvaluateProjectLabel
 * @package Evaluation\Navigation\Invokable
 */
final class EvaluateProjectLabel extends AbstractNavigationInvokable
{
    public function __invoke(Mvc $page): void
    {
        $label = $this->translator->translate('txt-evaluation');

        if ($this->getEntities()->containsKey(Project::class)) {
            /** @var Project $project */
            $project = $this->getEntities()->get(Project::class);
            $this->getEntities()->set(Call::class, $project->getCall());
            $label = (string)sprintf($this->translator->translate('txt-evaluation-project-%s'), $project);
        }
        $page->set('label', $label);
    }
}
