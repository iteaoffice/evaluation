<?php

declare(strict_types=1);

namespace Evaluation\View\Helper\Report;

use Evaluation\View\Helper\AbstractLink;
use Zend\Stdlib\Parameters;

/**
 * Class PresentationLink
 *
 * @package Evaluation\View\Helper\Report
 */
final class PresentationLink extends AbstractLink
{
    public function __invoke(
        Parameters $parameters = null,
        string     $show = 'button'
    ): string {
        $this->setRoute('zfcadmin/evaluation/report/presentation');
        $this->setText($this->translator->translate('txt-download-presentation'));
        $this->setLinkIcon('fa fa-download');

        $this->query = $parameters->toArray();

        return $this->createLink($show);
    }
}
