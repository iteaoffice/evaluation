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
use Evaluation\Entity\Feedback;
use Project\Entity\Project;
use Project\Entity\Version\Version;
use Laminas\Navigation\Page\Mvc;

/**
 * Class FeedbackLabel
 * @package Evaluation\Navigation\Invokable
 */
final class FeedbackLabel extends AbstractNavigationInvokable
{
    public function __invoke(Mvc $page): void
    {
        $label = $this->translator->translate('txt-nav-view');

        $entities = $this->getEntities();
        if ($entities->containsKey(Feedback::class)) {
            /** @var Feedback $feedback */
            $feedback = $entities->get(Feedback::class);

            /** @var Version $version */
            $version = $feedback->getVersion();
            $entities->set(Version::class, $version);

            $page->setParams(array_merge(
                $page->getParams(),
                [
                    'id'     => $feedback->getId(),
                    'docRef' => $version->getProject()->getDocRef(),
                ]
            ));
            $entities->set(Project::class, $feedback->getVersion()->getProject());
            $label = sprintf(
                $this->translator->translate('txt-feedback-on-%s'),
                $feedback->getVersion()->getVersionType()->getDescription()
            );
        }

        if ($entities->containsKey(Version::class)) {
            /** @var Version $version */
            $version = $entities->get(Version::class);

            $page->setParams(array_merge(
                $page->getParams(),
                [
                    'id'     => $version->getId(),
                    'docRef' => $version->getProject()->getDocRef(),
                ]
            ));
            $entities->set(Project::class, $version->getProject());
            $label = sprintf(
                $this->translator->translate('txt-create-feedback-on-%s'),
                $version->getVersionType()->getDescription()
            );
        }
        $page->set('label', $label);
    }
}
