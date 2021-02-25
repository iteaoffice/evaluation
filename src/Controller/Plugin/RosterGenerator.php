<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Controller\Plugin;

use Doctrine\ORM\EntityNotFoundException;
use Evaluation\Options\ModuleOptions;
use Evaluation\Service\ReviewerService;
use Evaluation\Service\ReviewRosterService;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Laminas\Http\Headers;
use Laminas\Http\Response;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

use function array_keys;
use function array_merge;
use function in_array;
use function ksort;
use function ob_end_flush;
use function ob_get_clean;
use function ob_get_length;
use function ob_start;
use function sprintf;
use function ucfirst;

/**
 * Class RosterGenerator
 *
 * @package Evaluation\Controller\Plugin
 */
final class RosterGenerator extends AbstractPlugin
{
    private string $type; // The roster type (PO/FPP/PPR/CR)
    private array $rosterData;
    private bool $onlineReview = false;
    private array $reviewers = [];
    private Spreadsheet $spreadsheet; // The main spreadsheet object the roster will be written to
    private ReviewRosterService $reviewRosterService;
    private TranslatorInterface $translator;
    private ModuleOptions $moduleOptions;
    private array $log;

    public function __construct(
        ReviewRosterService $reviewRosterService,
        TranslatorInterface $translator,
        ModuleOptions $moduleOptions
    ) {
        $this->reviewRosterService = $reviewRosterService;
        $this->translator          = $translator;
        $this->moduleOptions       = $moduleOptions;
    }

    /**
     * @param string   $type                  Constants defined in Project\Entity\Evaluation\Report\Type
     * @param string   $configFile            Path to the uploaded Excel file with additional review config (formerly reviewers.txt)
     * @param int      $reviewersPerProject   Minimum number of reviewers assigned per project
     * @param bool     $includeSpareReviewers Include spare reviewers in the minimum number of reviewers assigned
     * @param bool     $onlineReview          Whether the review is online (no rounds) or physical (use review rounds)
     * @param int|null $forceProjectsPerRound Overrule the calculated number of projects per round
     *
     * @return RosterGenerator
     * @throws \Exception
     * @throws EntityNotFoundException
     */
    public function __invoke(
        string $type,
        string $configFile,
        int    $reviewersPerProject,
        bool   $includeSpareReviewers = false,
        bool   $onlineReview = false,
        ?int   $forceProjectsPerRound = null
    ): RosterGenerator {
        $this->type = $type;
        $config = $this->reviewRosterService->parseConfigFile($configFile);
        $this->onlineReview = $onlineReview;
        $this->rosterData = $this->reviewRosterService->generateRosterData(
            $type,
            $config,
            $reviewersPerProject,
            $includeSpareReviewers,
            $onlineReview,
            $forceProjectsPerRound
        );
        $this->log = $this->reviewRosterService->getLog();
        $this->reviewers = array_merge($config['present'], $config['spare']);
        ksort($this->reviewers);
        $this->generateSpreadsheet();

        return $this;
    }

    /**
     * @throws \Exception
     */
    private function generateSpreadsheet()
    {
        // General setup
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator($this->moduleOptions->getReportAuthor());
        $spreadsheet->getProperties()->setTitle(
            sprintf(
                $this->translator->translate('txt-review-roster-for-%s'),
                $this->type
            )
        );

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(ucfirst($this->translator->translate('txt-review-roster')));
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(true);
        $sheet->getPageSetup()->setFitToHeight(false);

        // Header
        $assignments = $this->getAssignmentTotals();
        $sheet->getRowDimension(1)->setRowHeight(-1); // Auto-height
        $col = 'D';
        foreach ($this->reviewers as $handle => $reviewerData) {
            ++$col;
            $sheet->getStyle($col . '1')->getAlignment()->setTextRotation(90);
            $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getColumnDimension($col)->setWidth(8);
            $reviewer = sprintf('%s (%s)', $reviewerData['name'], $reviewerData['organisation']);
            $sheet->setCellValue($col . '1', $reviewer);
            $sheet->getStyle($col . '2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue($col . '2', sprintf('%s (%d)', $handle, $assignments[$handle]));
        }
        $lastColumn = $col;
        $sheet->getStyle('A2:' . $lastColumn . '2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(
            'DDDDDD'
        );

        // Collapse row 1
        $sheet->getRowDimension(1)
            ->setOutlineLevel(1)
            ->setCollapsed(true)
            ->setVisible(false);

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->setCellValue('A2', $this->translator->translate('txt-call'));
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->setCellValue('B2', '#');
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->setCellValue('C2', $this->translator->translate('txt-project'));
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->setCellValue('D2', $this->translator->translate('txt-evaluation'));
        // Freeze the header
        $sheet->freezePane('A3');

        if (in_array($this->type, [ReviewerService::TYPE_PO, ReviewerService::TYPE_FPP, ReviewerService::TYPE_PPR])) {
            if ($this->onlineReview) {
                $this->fillPoFppPprOnlineSpreadsheet($sheet);
            } else {
                $this->fillPoFppPprSpreadsheet($sheet, $lastColumn);
            }
        } elseif ($this->type === ReviewerService::TYPE_CR) {
            $this->fillCrSpreadsheet($sheet, $lastColumn);
        }

        $logSheet = $spreadsheet->createSheet();
        $logSheet->setTitle(ucfirst($this->translator->translate('txt-log')));
        $logSheet->getColumnDimension('B')->setAutoSize(true);
        $logSheet->fromArray($this->log);

        $this->spreadsheet = $spreadsheet;
    }

    /**
     * Get the assignment totals for each handle
     *
     * @return array
     */
    private function getAssignmentTotals(): array
    {
        $assignments = [];
        $assigned = [
            ReviewRosterService::REVIEWER_ASSIGNED,
            ReviewRosterService::REVIEWER_PRIMARY,
            ReviewRosterService::REVIEWER_SPARE,
            ReviewRosterService::REVIEWER_EXTRA
        ];
        foreach ($this->rosterData as $projects) {
            foreach ($projects as $project) {
                foreach ($project['scores'] as $handle => $ignoredOrAssigned) {
                    if (! isset($assignments[$handle])) {
                        $assignments[$handle] = 0;
                    }
                    if (in_array($ignoredOrAssigned, $assigned)) {
                        $assignments[$handle]++;
                    }
                }
            }
        }

        return $assignments;
    }

    /**
     * @param Worksheet $sheet
     * @param string    $lastColumn
     *
     * @throws Exception
     */
    private function fillPoFppPprSpreadsheet(Worksheet $sheet, string $lastColumn): void
    {
        $row = 3;
        foreach ($this->rosterData as $round => $projects) {
            $this->parseSection($sheet, $row, $lastColumn, (string)$round);
            foreach ($projects as $project) {
                // Add review history
                $this->parseReviewHistory($sheet, $row, $project['data']);
                // Add current roster
                $sheet->setCellValue('A' . $row, $project['data']['call']);
                $sheet->setCellValue('B' . $row, $project['data']['number']);
                $sheet->setCellValue('C' . $row, $project['data']['name']);
                $sheet->getStyle('D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('EEEEEE');
                $sheet->setCellValue(
                    'D' . $row,
                    $this->type . sprintf(' (%s)', $this->translator->translate('txt-upcoming'))
                );
                $col = 'E';
                foreach (array_keys($this->reviewers) as $handle) {
                    $cell = $col . $row;
                    $this->parseCell($sheet, $cell, $project['scores'][$handle]);
                    $col++;
                }
                $row++;
            }
        }
    }

    /**
     * @throws Exception
     */
    private function fillPoFppPprOnlineSpreadsheet(Worksheet $sheet): void
    {
        $row = 3;
        foreach ($this->rosterData as $projects) {
            foreach ($projects as $project) {
                // Add review history
                $this->parseReviewHistory($sheet, $row, $project['data']);
                // Add current roster
                $sheet->setCellValue('A' . $row, $project['data']['call']);
                $sheet->setCellValue('B' . $row, $project['data']['number']);
                $sheet->setCellValue('C' . $row, $project['data']['name']);
                $sheet->getStyle('D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('EEEEEE');
                $sheet->setCellValue(
                    'D' . $row,
                    $this->type . sprintf(' (%s)', $this->translator->translate('txt-upcoming'))
                );
                $col = 'E';
                foreach (array_keys($this->reviewers) as $handle) {
                    $cell = $col . $row;
                    $this->parseCell($sheet, $cell, $project['scores'][$handle]);
                    $col++;
                }
                $row++;
            }
        }
    }

    /**
     * @param Worksheet $sheet
     * @param int       $row
     * @param string    $lastColumn
     * @param string    $section
     *
     * @throws Exception
     */
    private function parseSection(Worksheet $sheet, int &$row, string $lastColumn, string $section): void
    {
        $cellA = 'A' . $row;
        $cellRow = $cellA . ':' . $lastColumn . $row;
        $sheet->mergeCells($cellRow);
        $sheet->getStyle($cellA)->getAlignment()->setIndent(1);
        $sheet->getStyle($cellRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($cellA)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('A7D8B8');
        if ($this->type === ReviewerService::TYPE_CR) {
            $sheet->setCellValue($cellA, sprintf('%s %s', ucfirst($this->translator->translate('txt-call')), $section));
        } else {
            $sheet->setCellValue(
                $cellA,
                sprintf('%s %d', ucfirst($this->translator->translate('txt-round')), $section)
            );
        }
        $row++;
    }

    /**
     * Add review history to a project
     *
     * @param Worksheet $sheet
     * @param int       $row
     * @param array     $projectData
     *
     * @throws Exception
     */
    private function parseReviewHistory(Worksheet $sheet, int &$row, array $projectData): void
    {
        foreach ($projectData['history'] as $historyLine) {
            foreach ($historyLine as $type => $reviewers) {
                if (! empty($reviewers)) {
                    $sheet->setCellValue('A' . $row, $projectData['call']);
                    $sheet->setCellValue('B' . $row, $projectData['number']);
                    $sheet->setCellValue('C' . $row, $projectData['name']);
                    $sheet->setCellValue('D' . $row, $type);
                    $col = 'E';
                    foreach (array_keys($this->reviewers) as $handle) {
                        $cell = $col . $row;
                        if (in_array($handle, $reviewers)) {
                            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setRGB('33AA33');
                            $sheet->setCellValue($cell, 'R');
                        } elseif (in_array($handle, $projectData['ignored'])) {
                            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_PATTERN_DARKDOWN)
                                ->setRotation(45)->getEndColor()->setRGB('FF8888');
                        }
                        $col++;
                    }
                    // Collapse row
                    $sheet->getRowDimension($row)
                        ->setOutlineLevel(1)
                        ->setCollapsed(true)
                        ->setVisible(false);
                    $row++;
                }
            }
        }
    }

    /**
     * @param Worksheet $sheet
     * @param string    $cell
     * @param int       $ignoredOrAssigned
     */
    private function parseCell(Worksheet $sheet, string $cell, int $ignoredOrAssigned): void
    {
        // Assigned reviewer
        if ($ignoredOrAssigned === ReviewRosterService::REVIEWER_ASSIGNED) {
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('33AA33');
            $sheet->setCellValue($cell, 'R');
        } // Primary reviewer
        elseif ($ignoredOrAssigned === ReviewRosterService::REVIEWER_PRIMARY) {
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('118811');
            $sheet->getStyle($cell)->getFont()->setColor(new Color(Color::COLOR_WHITE));
            $sheet->setCellValue($cell, 'P');
        } // Spare reviewer
        elseif ($ignoredOrAssigned === ReviewRosterService::REVIEWER_SPARE) {
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('77EE77');
            $sheet->setCellValue($cell, 'S');
        } // Extra spare reviewer
        elseif ($ignoredOrAssigned === ReviewRosterService::REVIEWER_EXTRA_SPARE) {
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('77EE77');
            $sheet->setCellValue($cell, 'ES');
        } // Extra present reviewer
        elseif ($ignoredOrAssigned === ReviewRosterService::REVIEWER_EXTRA) {
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('77EE77');
            $sheet->setCellValue($cell, 'E');
        } // Ignored reviewer
        elseif ($ignoredOrAssigned === ReviewRosterService::REVIEWER_IGNORED) {
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_PATTERN_DARKDOWN)
                ->setRotation(45)->getEndColor()->setRGB('FF8888');
        }
    }

    /**
     * @param Worksheet $sheet
     * @param string    $lastColumn
     */
    private function fillCrSpreadsheet(Worksheet $sheet, string $lastColumn): void
    {
        $row = 3;
        foreach ($this->rosterData as $call => $projects) {
            $this->parseSection($sheet, $row, $lastColumn, $call);
            foreach ($projects as $project) {
                // Add review history
                $this->parseReviewHistory($sheet, $row, $project['data']);

                // Add current roster
                $sheet->setCellValue('A' . $row, $project['data']['call']);
                $sheet->setCellValue('B' . $row, $project['data']['number']);
                $sheet->setCellValue('C' . $row, $project['data']['name']);
                $sheet->getStyle('D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('EEEEEE');
                $sheet->setCellValue(
                    'D' . $row,
                    $this->type . sprintf(' (%s)', $this->translator->translate('txt-upcoming'))
                );
                $col = 'E';
                foreach (array_keys($this->reviewers) as $handle) {
                    $cell = $col . $row;
                    $this->parseCell($sheet, $cell, $project['scores'][$handle]);
                    $col++;
                }
                $row++;
            }
        }
    }

    /**
     * @return Response
     */
    public function parseResponse(): Response
    {
        $response = new Response();
        if (! ($this->spreadsheet instanceof Spreadsheet)) {
            return $response->setStatusCode(Response::STATUS_CODE_404);
        }

        /** @var Xls $writer */
        $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');

        ob_start();
        $gzip = false;
        // Gzip the output when possible. @see http://php.net/manual/en/function.ob-gzhandler.php
        if (ob_start('ob_gzhandler')) {
            $gzip = true;
        }
        $writer->save('php://output');
        if ($gzip) {
            ob_end_flush(); // Flush the gzipped buffer into the main buffer
        }
        $contentLength = ob_get_length();

        // Prepare the response
        $response->setContent(ob_get_clean());
        $response->setStatusCode(Response::STATUS_CODE_200);
        $headers = new Headers();
        $headers->addHeaders(
            [
                'Content-Disposition' => 'attachment; filename="' . $this->spreadsheet->getProperties()->getTitle()
                    . '.xlsx"',
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Length'      => $contentLength,
                'Expires'             => '@0', // @0, because ZF2 parses date as string to \DateTime() object
                'Cache-Control'       => 'must-revalidate',
                'Pragma'              => 'public',
            ]
        );
        if ($gzip) {
            $headers->addHeaders(['Content-Encoding' => 'gzip']);
        }
        $response->setHeaders($headers);

        return $response;
    }

    public function getSpreadsheet(): Spreadsheet
    {
        return $this->spreadsheet;
    }
}
