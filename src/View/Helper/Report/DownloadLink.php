<?php

declare(strict_types=1);

namespace Evaluation\View\Helper\Report;

use Evaluation\View\Helper\AbstractLink;

/**
 * Class ReportDownloadLink
 * @package Evaluation\View\Helper\Evaluation
 */
final class DownloadLink extends AbstractLink
{
    /**
     * @var int
     */
    private $status;

    public function __invoke(
        int    $status = null,
        string $action = 'download-combined',
        string $show = 'button'
    ): string {
        $this->status = $status;
        $this->action = $action;
        $this->show   = $show;

        return $this->createLink();
    }

    public function parseAction(): void
    {
        switch ($this->getAction()) {
            case 'download-combined':
                $this->setRouter('community/evaluation/report2/download-combined');
                if ($this->status !== null) {
                    $this->addRouterParam('status', $this->status);
                }
                $this->setText($this->translator->translate('txt-download-all'));
                break;
            default:
                throw new \Exception(sprintf("%s is an incorrect action for %s", $this->getAction(), __CLASS__));
        }
    }
}
