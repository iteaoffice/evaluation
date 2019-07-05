<?php

declare(strict_types=1);

namespace Evaluation\View\Helper\Report;

use Evaluation\View\Helper\AbstractLink;

/**
 * Class DownloadLink
 *
 * @package Evaluation\View\Helper\Report
 */
final class DownloadLink extends AbstractLink
{
    public function __invoke(
        int $status = null,
        string $action = 'download-combined',
        string $show = 'button'
    ): string {
        $this->reset();

        $this->setRoute('community/evaluation/report/download-combined');
        if (null !== $status) {
            $this->addRouteParam('status', $status);
        }
        $this->setText($this->translator->translate('txt-download-all'));

        return $this->createLink($show);
    }
}
