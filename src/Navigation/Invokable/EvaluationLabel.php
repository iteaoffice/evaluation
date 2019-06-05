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

namespace Evaluation\Navigation\Invokable;

use Admin\Navigation\Invokable\AbstractNavigationInvokable;
use Program\Entity\Call\Call;
use Evaluation\Entity\Evaluation;
use Project\Entity\Project;
use Zend\Navigation\Page\Mvc;

/**
 * Class EvaluationLabel
 * @package Evaluation\Navigation\Invokable
 */
final class EvaluationLabel extends AbstractNavigationInvokable
{
    public function __invoke(Mvc $page): void
    {
        if ($this->getEntities()->containsKey(Call::class)) {

            /** @var Call $call */
            $call = $this->getEntities()->get(Call::class);
            $page->setActive(true);
            $page->setParams(
                array_merge(
                    $page->getParams(),
                    [
                        'call' => $call->getId(),
                    ]
                )
            );

            $page->set('label', (string)sprintf($this->translator->translate("txt-evaluation-call-%s"), (string)$call));
        }

        // This label can be used in 2 ways (for the call and for the project
        if ($this->getEntities()->containsKey(Evaluation::class)) {

            /** @var Evaluation $evaluation */
            $evaluation = $this->getEntities()->get(Evaluation::class);
            $project    = $evaluation->getProject();

            $this->getEntities()->set(Project::class, $project);

            $page->setParams(
                array_merge(
                    $page->getParams(),
                    [
                        'id' => $evaluation->getId(),
                    ]
                )
            );

            $page->set('label', (string)sprintf($this->translator->translate("txt-evaluation-project-%s"), (string)$project));
        }
    }
}
