<?php

declare(strict_types=1);

namespace Evaluation\View\Helper\Report;

use General\ValueObject\Link\Link;
use General\View\Helper\AbstractLink;

/**
 * Class DownloadLink
 * @package Evaluation\View\Helper\Report
 */
final class DownloadLink extends AbstractLink
{
    public function __invoke(
        int    $status = null,
        string $action = 'download-combined',
        string $show = 'button'
    ): string
    {
        return $this->parse(Link::fromArray([
            'icon'        => 'fa-download',
            'route'       => 'community/evaluation/report/download-combined',
            'text'        => $this->translator->translate('txt-download-all'),
            'routeParams' => (null === $status) ? [] : ['status' => $status],
            'show'        => $show
        ]));
    }
}
