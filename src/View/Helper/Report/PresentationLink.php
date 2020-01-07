<?php

declare(strict_types=1);

namespace Evaluation\View\Helper\Report;

use General\ValueObject\Link\Link;
use General\View\Helper\AbstractLink;
use Laminas\Stdlib\Parameters;

/**
 * Class PresentationLink
 * @package Evaluation\View\Helper\Report
 */
final class PresentationLink extends AbstractLink
{
    public function __invoke(
        Parameters $parameters = null,
        string     $show = 'button'
    ): string {
        return $this->parse(Link::fromArray([
            'icon'        => 'fa-download',
            'route'       => 'zfcadmin/evaluation/report/presentation',
            'text'        => $this->translator->translate('txt-download-presentation'),
            'queryParams' => null === $parameters ? [] : $parameters->toArray(),
            'show'        => $show
        ]));
    }
}
