<?php
/**
 * ITEA Office all rights reserved
 *
 * @category    Affiliation
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2019 ITEA Office (https://itea3.org)
 */

declare(strict_types=1);

namespace Evaluation\Controller\Plugin;

use setasign\Fpdi\Tcpdf\Fpdi as TcpdfFpdi;

/**
 * Class ReportPdf
 *
 * @package Project\Controller\Plugin\Evaluation
 */
final class ReportPdf extends TcpdfFpdi
{
    public const DEFAULT_FONT = 'freesans';

    /**
     * @var string
     */
    private $templatePageId;

    /**
     * @var string
     */
    private $templateFile;

    public function Header(): void
    {
        if (null === $this->templatePageId) {
            if (!\file_exists($this->templateFile)) {
                throw new \RuntimeException(\sprintf("Template %s cannot be found", $this->templateFile));
            }
            $this->setSourceFile($this->templateFile);
            $this->templatePageId = $this->importPage(1);
        }
        $this->useTemplate($this->templatePageId);
        $this->SetFont(self::DEFAULT_FONT, 'N', 15);
        $this->SetTextColor(0);
        $this->SetXY(PDF_MARGIN_LEFT, 5);
    }

    public function Footer(): void
    {
        $this->SetY(-10);
        $this->SetFont(self::DEFAULT_FONT, 'N', 9);
        $this->MultiCell(
            0,
            0,
            \sprintf('Page %s of %s', $this->PageNo(), $this->getAliasNbPages()),
            0,
            'C',
            false,
            0,
            PDF_MARGIN_LEFT,
            $this->y
        );
    }

    public function setTemplate(string $templateFile): void
    {
        $this->templateFile = $templateFile;
    }
}
