<?php

/**
*
 * @author      Bart van Eijck <bart.van.eijck@itea3.org>
 * @copyright   Copyright (c) 2019 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\Controller\Plugin\Report;

use Contact\Entity\Contact;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Evaluation\Entity\Report as EvaluationReport;
use Project\Entity\Version\Reviewer as VersionReviewer;
use Evaluation\Service\EvaluationReportService;
use Laminas\Http\Headers;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Mvc\Controller\PluginManager;
use ZipArchive;
use function file_exists;
use function file_get_contents;
use function filesize;
use function ob_get_clean;
use function ob_start;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Class ExcelDownload
 * @package Evaluation\Controller\Plugin\Report
 */
final class ExcelDownload extends AbstractPlugin
{
    /**
     * @var EvaluationReportService
     */
    private $evaluationReportService;

    /**
     * @var ExcelExport
     */
    private $reportExcelExport;

    /**
     * @var string
     */
    private $zipTempFile = '';

    public function __construct(
        EvaluationReportService $evaluationReportService,
        PluginManager           $pluginManager
    ) {
        $this->evaluationReportService = $evaluationReportService;
        $this->reportExcelExport       = $pluginManager->get(ExcelExport::class);
    }

    /*
     * Download Excel .xlsx files combined in a .zip
     */
    public function __invoke(Contact $contact, int $status, bool $forDistribution = false): ExcelDownload
    {
        $this->zipTempFile = tempnam(sys_get_temp_dir(), 'zip');
        $zip               = new ZipArchive();
        $zip->open($this->zipTempFile, ZipArchive::OVERWRITE);

        $windows = $this->evaluationReportService->findReviewReportsByContact($contact, $status);
        foreach ($windows as $window) {
            foreach ($window['reviews'] as $content) {
                $report = $content;
                if (! ($content instanceof EvaluationReport)) {
                    if ($content instanceof VersionReviewer) {
                        $reportVersion = $this->evaluationReportService->findReportVersionForProjectVersion(
                            $content->getVersion()
                        );
                    } else {
                        $reportVersion = $this->evaluationReportService->findReportVersionForProjectReport();
                    }
                    $report = $this->evaluationReportService->prepareEvaluationReport($reportVersion, $content->getId());
                }
                $reportExcelExport = $this->reportExcelExport;
                $excel             = $reportExcelExport($report, false, $forDistribution)->getExcel();
                $fileName          = $reportExcelExport->parseFileName();

                /** @var Xlsx $writer */
                $writer = IOFactory::createWriter($excel, 'Xlsx');
                $writer->setIncludeCharts(! $forDistribution);
                ob_start();
                $writer->save('php://output');
                $zip->addFromString($fileName, ob_get_clean());
            }
        }
        $zip->close();

        return $this;
    }

    /**
     * @return Response
     */
    public function parseResponse(): Response
    {
        $response = new Response();
        if (! file_exists($this->zipTempFile)) {
            return $response->setStatusCode(Response::STATUS_CODE_404);
        }

        // Prepare the response
        $response->setContent(file_get_contents($this->zipTempFile));
        $response->setStatusCode(Response::STATUS_CODE_200);
        $headers = new Headers();
        $headers->addHeaders([
            'Content-Disposition' => 'attachment; filename="Evaluation reports.zip"',
            'Content-Type'        => 'application/zip',
            'Content-Length'      => filesize($this->zipTempFile),
            'Expires'             => '0',
            'Cache-Control'       => 'must-revalidate',
            'Pragma'              => 'public',
        ]);
        $response->setHeaders($headers);

        return $response;
    }

    /**
     * @return string
     */
    public function getZipTempFile(): string
    {
        return $this->zipTempFile;
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        if (file_exists($this->zipTempFile)) {
            unlink($this->zipTempFile);
        }
    }
}
