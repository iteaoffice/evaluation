<?php

/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @category    Project
 *
 * @author      Bart van Eijck <bart.van.eijck@itea3.org>
 * @copyright   Copyright (c) 2004-2018 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\Controller\Plugin\Report;

use DateTime;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Entity\Report\Criterion;
use Evaluation\Entity\Report\Criterion\Topic;
use Evaluation\Entity\Report\Result;
use Evaluation\Options\ModuleOptions;
use Evaluation\Service\EvaluationReportService;
use Evaluation\Service\ReviewerService;
use Organisation\Entity\Organisation;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Chart\Axis;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Project\Entity\Challenge;
use Project\Entity\Rationale;
use Project\Entity\Report\Reviewer as ReportReviewer;
use Project\Entity\Version\Reviewer as VersionReviewer;
use Project\Entity\Version\Version;
use Project\Service\ProjectService;
use Project\Service\VersionService;
use Zend\Http\Headers;
use Zend\Http\Response;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Json\Json;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use function array_keys;
use function array_map;
use function ceil;
use function count;
use function end;
use function html_entity_decode;
use function implode;
use function in_array;
use function number_format;
use function ob_end_flush;
use function ob_get_clean;
use function ob_get_length;
use function ob_start;
use function reset;
use function sort;
use function sprintf;
use function strlen;
use function ucfirst;

/**
 * Class ExcelExport
 *
 * @package Evaluation\Controller\Plugin\Report
 */
final class ExcelExport extends AbstractPlugin
{
    /**
     * @var EvaluationReportService
     */
    private $evaluationReportService;
    /**
     * @var ProjectService
     */
    private $projectService;
    /**
     * @var VersionService
     */
    private $versionService;
    /**
     * @var ModuleOptions
     */
    private $moduleOptions;
    /**
     * @var EvaluationReport
     */
    private $evaluationReport;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Spreadsheet
     */
    private $excel;

    /**
     * @var bool
     */
    private $forDistribution = false;

    public function __construct(
        EvaluationReportService $evaluationReportService,
        ProjectService          $projectService,
        VersionService          $versionService,
        ModuleOptions           $moduleOptions,
        TranslatorInterface     $translator
    ) {
        $this->evaluationReportService = $evaluationReportService;
        $this->projectService          = $projectService;
        $this->versionService          = $versionService;
        $this->moduleOptions           = $moduleOptions;
        $this->translator              = $translator;
    }

    public function __invoke(
        EvaluationReport $evaluationReport,
        bool             $isFinal = false,
        bool             $forDistribution = false
    ): ExcelExport {
        $this->evaluationReport = $evaluationReport;
        $this->excel            = new Spreadsheet();
        $this->forDistribution  = $forDistribution;

        $displaySheet = $this->excel->getActiveSheet();
        $displaySheet->setShowGridlines(false);
        $displaySheet->setTitle($this->translator->translate('txt-evaluation-report'));
        $displaySheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $displaySheet->getPageSetup()->setFitToWidth(1);
        $displaySheet->getPageSetup()->setFitToHeight(0);
        $displaySheet->getColumnDimension('A')->setWidth(50);
        $displaySheet->getColumnDimension('B')->setWidth(20);
        $displaySheet->getColumnDimension('C')->setWidth(80);
        if ($this->forDistribution) {
            $displaySheet->getProtection()->setSheet(true);
        }

        // Set the dropdown lookup data in a hidden sheet
        $lookupSheet = $this->excel->createSheet(1);
        $lookupSheet->setTitle('LookupData');
        $lookupSheet->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
        // Scores
        $row = 1;
        foreach (Result::getScoreValues() as $scoreValue => $scoreLabel) {
            $lookupSheet->setCellValue('A' . $row, $this->translator->translate($scoreLabel));
            $lookupSheet->setCellValue('B' . $row, $scoreValue);
            $row++;
        }
        $this->excel->addNamedRange(
            new NamedRange(
                'scores',
                $lookupSheet,
                'A1:A' . count(Result::getScoreValues())
            )
        );
        // Yes/no
        $lookupSheet->setCellValue('D1', $this->translator->translate('txt-yes'));
        $lookupSheet->setCellValue('E1', 'Yes');
        $lookupSheet->setCellValue('D2', $this->translator->translate('txt-no'));
        $lookupSheet->setCellValue('E2', 'No');
        $this->excel->addNamedRange(new NamedRange('yesNo', $lookupSheet, 'D1:D2'));

        // Steering group decision
        $row = 1;
        $reportType = $this->evaluationReportService->parseEvaluationReportType($evaluationReport);
        $reportOrCr = [
            EvaluationReport\Type::TYPE_REPORT,
            EvaluationReport\Type::TYPE_MAJOR_CR_VERSION,
            EvaluationReport\Type::TYPE_MINOR_CR_VERSION
        ];
        $scores = in_array($reportType, $reportOrCr, true)
            ? EvaluationReport::getReportScores() : EvaluationReport::getVersionScores();

        foreach ($scores as $scoreValue => $scoreLabel) {
            $lookupSheet->setCellValue('G' . $row, $this->translator->translate($scoreLabel));
            $lookupSheet->setCellValue('H' . $row, $scoreValue);
            $row++;
        }
        $this->excel->addNamedRange(
            new NamedRange('finalScores', $lookupSheet, 'G1:G' . count($scores))
        );

        // Project status (PPR-only)
        if ($this->evaluationReport->getProjectReportReport() !== null) {
            $row = 1;
            $statuses = EvaluationReport\ProjectReport::getProjectStatuses();
            foreach ($statuses as $statusValue => $statusLabel) {
                $lookupSheet->setCellValue('J' . $row, $this->translator->translate($statusLabel));
                $lookupSheet->setCellValue('K' . $row, $statusValue);
                $row++;
            }
            $this->excel->addNamedRange(
                new NamedRange('projectStatuses', $lookupSheet, 'J1:J' . count($statuses))
            );
        }

        // Add hidden sheets
        $dataSheet = null;
        $dataRow   = 1;
        $allTopics = [];
        $hasTopics = false;
        if (!$this->forDistribution) {
            // Add a hidden import data sheet
            $dataSheet = $this->excel->createSheet(2);
            $dataSheet->setTitle('ImportData');
            $dataSheet->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
            $allTopics = $this->evaluationReport->getVersion()->getTopics()->toArray();
            $hasTopics = (count($allTopics) > 0);

            if ($hasTopics) {
                // Add a hidden chart data sheet
                $chartDataSheet = $this->excel->createSheet(3);
                $chartDataSheet->setTitle('ChartData');
                $chartDataSheet->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
                $chartDataRow = 1;
                $column = 'A';
                /** @var Topic $topic */
                foreach ($allTopics as $topic) {
                    $chartDataSheet->setCellValue($column . $chartDataRow, $topic->getTopic());
                    $column++;
                }
                $chartDataRow += 2;
            }
        }

        // Add the results
        $row             = 1;
        $categoryCount   = 1;
        $currentCategory = '';
        $currentType     = '';

        /** @var Result $result */
        foreach ($this->evaluationReportService->getSortedResults($evaluationReport) as $result) {
            $type = $result->getCriterionVersion()->getType()->getType();
            $category = $result->getCriterionVersion()->getType()->getCategory()->getCategory();

            if ($category !== $currentCategory) {
                $this->parseCategory($displaySheet, $row, $category);
                if ($categoryCount === 1) {
                    $this->parseType($displaySheet, $row, $this->translator->translate('txt-project-details'));
                    $this->parseProjectData($displaySheet, $row);

                    // No STG decision in distributed export PO/FPP evaluation
                    $hideFor = [EvaluationReport\Type::TYPE_PO_VERSION, EvaluationReport\Type::TYPE_FPP_VERSION];
                    if (!$this->forDistribution || !in_array($reportType, $hideFor, true)) {
                        $this->parseType($displaySheet, $row, $this->translator->translate('txt-review-details'));
                        $this->parseSteeringGroupData($displaySheet, $dataSheet, $reportType, $row);
                    }
                }
                $currentCategory = $category;
            }

            if (($type !== $currentType)
                && (
                    !$this->forDistribution
                    || !$this->evaluationReportService->typeIsConfidential(
                        $result->getCriterionVersion()->getType(),
                        $this->evaluationReport->getVersion()
                    )
                )
            ) {
                $this->parseType($displaySheet, $row, $type);
                $currentType = $type;
            }

            if (!$this->forDistribution || !$result->getCriterionVersion()->getConfidential()) {
                $this->parseResult($displaySheet, $dataSheet, $result, $row, $dataRow);
            }

            if (!$this->forDistribution && isset($chartDataSheet) && $hasTopics) {
                $this->parseChartData($chartDataSheet, $result, $allTopics, $chartDataRow, $dataRow);
            }

            $categoryCount++;
        }

        if (!$this->forDistribution && $hasTopics) {
            $chart = $this->parseRadarChart($allTopics);
            $chart->setTopLeftPosition('E2');
            $chart->setBottomRightPosition('K20');
            $displaySheet->addChart($chart);
        }

        $label = EvaluationReportService::parseLabel($evaluationReport);
        if ($isFinal) {
            $title = sprintf($this->translator->translate('txt-final-evaluation-report-for-%s'), $label);
        } else {
            $title = sprintf($this->translator->translate('txt-evaluation-report-for-%s'), $label);
        }
        if ($this->forDistribution) {
            $title .= ' ' . ucfirst($this->translator->translate('txt-distribution'));
        }

        $this->excel->getProperties()->setCreator($this->moduleOptions->getReportAuthor());
        $this->excel->getProperties()->setTitle($title);

        return $this;
    }

    private function parseCategory(Worksheet $displaySheet, int &$row, string $label): void
    {
        if ($row > 1) {
            $displaySheet->mergeCells('A' . $row . ':C' . $row);
            $row++;
        }
        $cellA = 'A' . $row;
        $cellAC = $cellA . ':C' . $row;
        $displaySheet->mergeCells($cellAC);
        $displaySheet->getRowDimension($row)->setRowHeight(25);
        $displaySheet->getStyle($cellAC)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $displaySheet->getStyle($cellA)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $displaySheet->getStyle($cellA)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $displaySheet->getStyle($cellA)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('A7D8B8');
        $displaySheet->setCellValue('A' . $row, $label);
        $row++;
    }

    private function parseType(Worksheet $displaySheet, int &$row, string $label): void
    {
        $displaySheet->getRowDimension($row)->setRowHeight(5);
        $row++;
        $cellA = 'A' . $row;
        $cellAC = $cellA . ':C' . $row;
        $displaySheet->mergeCells($cellAC);
        $displaySheet->getRowDimension($row)->setRowHeight(20);
        $displaySheet->getStyle($cellA)->getAlignment()->setIndent(1);
        $displaySheet->getStyle($cellAC)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $displaySheet->getStyle($cellA)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDDDDD');
        $displaySheet->getStyle($cellA)->getFont()->setItalic(true);
        $displaySheet->setCellValue($cellA, $label);
        $row++;
        $displaySheet->getRowDimension($row)->setRowHeight(5);
        $row++;
    }

    private function parseProjectData(Worksheet $displaySheet, int &$row): void
    {
        // Project name + report/version
        $project = EvaluationReportService::getProject($this->evaluationReport);
        $this->parseCriterionLabel($displaySheet, $row, $this->translator->translate('txt-project-name'));
        $displaySheet->setCellValue('B' . $row, $project->parseFullName());
        $displaySheet->setCellValue(
            'C' . $row,
            EvaluationReportService::parseLabel($this->evaluationReport, '%3$s')
        );
        $row++;

        // Project title
        $this->parseCriterionLabel($displaySheet, $row, $this->translator->translate('txt-project-title'));
        $displaySheet->mergeCells('B' . $row . ':C' . $row);
        $displaySheet->setCellValue('B' . $row, $project->getTitle());
        $row++;

        // Project leader
        $this->parseCriterionLabel($displaySheet, $row, $this->translator->translate('txt-project-leader'));
        $displaySheet->setCellValue('B' . $row, $project->getContact()->parseFullName());
        /** @var Organisation $organisation */
        $organisation = $project->getContact()->getContactOrganisation()->getOrganisation();
        $displaySheet->setCellValue(
            'C' . $row,
            $organisation->getOrganisation() . ', ' . $organisation->getCountry()->getCountry()
        );
        $row++;

        // Latest project version (only for PPR)
        if ($this->evaluationReport->getProjectReportReport() !== null) {
            $this->parseCriterionLabel($displaySheet, $row, $this->translator->translate('txt-latest-project-version'));
            $latestVersion = $this->projectService->getLatestProjectVersion($project);

            if (null !== $latestVersion) {
                $displaySheet->setCellValue('B' . $row, $latestVersion->getVersionType()->getDescription());

                if ($latestVersion->isReviewed()) {
                    $action = sprintf(
                        $this->translator->translate('txt-reviewed-on-%s'),
                        $latestVersion->getDateReviewed()->format('j M Y')
                    );
                } else {
                    $action = sprintf(
                        $this->translator->translate('txt-submitted-on-%s'),
                        $latestVersion->getDateSubmitted()->format('j M Y')
                    );
                }
                $label = $this->versionService->parseStatus($latestVersion) . (empty($action) ? ''
                        : ' (' . $action . ')');
                $displaySheet->setCellValue('C' . $row, $label);
                $row++;
            }
        }

        // Project size
        $version = new Version();
        if ($this->evaluationReport->getProjectVersionReport() !== null) {
            /** @var EvaluationReport\ProjectVersion $projectVersionReport */
            $projectVersionReport = $this->evaluationReport->getProjectVersionReport();
            if ($projectVersionReport->getReviewer() !== null) {
                $version = $projectVersionReport->getReviewer()->getVersion();
            } elseif ($projectVersionReport->getVersion() !== null) {
                $version = $projectVersionReport->getVersion();
            }
        } else {
            $version = $this->projectService->getLatestProjectVersion($project);
        }
        $this->parseCriterionLabel($displaySheet, $row, $this->translator->translate('txt-project-size'));
        $displaySheet->setCellValue(
            'B' . $row,
            sprintf(
                '%s: %s k€',
                $this->translator->translate('txt-cost'),
                number_format(
                    $this->versionService->findTotalCostVersionByProjectVersion($version) / 1000,
                    0,
                    '.',
                    ','
                )
            )
        );
        $displaySheet->setCellValue(
            'C' . $row,
            sprintf(
                '%s: %s PY',
                $this->translator->translate('txt-effort'),
                number_format(
                    (float)$this->versionService->findTotalEffortVersionByProjectVersion($version),
                    2,
                    '.',
                    ','
                )
            )
        );

        $row++;

        // Project start and end date
        $this->parseCriterionLabel($displaySheet, $row, $this->translator->translate('txt-project-start-and-end-date'));
        $startDate = $this->projectService->parseOfficialDateStart($project);
        if ($startDate instanceof DateTime) {
            $displaySheet->setCellValue('B' . $row, $startDate->format('j M Y'));
        }
        $endDate = $this->projectService->parseOfficialDateEnd($project);
        if ($endDate instanceof DateTime) {
            $displaySheet->setCellValue('C' . $row, $endDate->format('j M Y'));
        }
        $row++;

        // Consortium
        $this->parseCriterionLabel($displaySheet, $row, $this->translator->translate('txt-consortium'));
        $displaySheet->mergeCells('B' . $row . ':C' . $row);

        $countries = array_map(
            static function (Rationale $rationale) {
                return $rationale->getCountry()->getCountry();
            },
            $project->getRationale()->toArray()
        );
        sort($countries);
        $displaySheet->setCellValue('B' . $row, implode(', ', $countries));
        $row++;

        // Challenge
        $this->parseCriterionLabel($displaySheet, $row, $this->translator->translate('txt-challenge'));
        $displaySheet->mergeCells('B' . $row . ':C' . $row);
        $challenges = array_map(
            function (Challenge $challenge) {
                return $challenge->getChallenge();
            },
            $project->getProjectChallenge()->toArray()
        );
        $displaySheet->setCellValue('B' . $row, implode(', ', $challenges));
        $row++;
    }

    private function parseCriterionLabel(
        Worksheet $displaySheet,
        int &$row,
        string $criterionLabel,
        ?string $helpBlock = null
    ): void {
        $criterionCell = 'A' . $row;
        $criterionAlignment = $displaySheet->getStyle($criterionCell)->getAlignment();
        $criterionAlignment->setVertical(Alignment::VERTICAL_CENTER);
        $criterionAlignment->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $criterionAlignment->setIndent(1);
        $criterionAlignment->setWrapText(true);
        $displaySheet->getStyle($criterionCell)->getFont()->setBold(true);
        $displaySheet->setCellValue($criterionCell, $criterionLabel);
        if ($helpBlock !== null) {
            $helpBlock = html_entity_decode($helpBlock);
            if (!empty($helpBlock)) {
                $displaySheet->getComment($criterionCell)->setWidth('320pt');
                $height = ceil(strlen($helpBlock) / 65) * 14; // By no means accurate, but it will do
                $displaySheet->getComment($criterionCell)->setHeight($height . 'pt');
                $displaySheet->getComment($criterionCell)->getText()->createTextRun($helpBlock)->getFont()->setSize(9);
            }
        }
    }

    private function parseSteeringGroupData(
        Worksheet $displaySheet,
        ?Worksheet $dataSheet,
        int $reportType,
        int &$row
    ): void {
        // Stg decision
        $displaySheet->getRowDimension($row)->setRowHeight(20);
        if (in_array(
            $reportType,
            [EvaluationReport\Type::TYPE_MAJOR_CR_VERSION, EvaluationReport\Type::TYPE_MINOR_CR_VERSION],
            true
        )
        ) {
            $this->parseCriterionLabel(
                $displaySheet,
                $row,
                sprintf($this->translator->translate('txt-steering-group-decision-on-%s'), ReviewerService::TYPE_CR),
                'Please indicate whether you approve or reject the CR'
            );
        } elseif ($reportType === EvaluationReport\Type::TYPE_REPORT) {
            $this->parseCriterionLabel(
                $displaySheet,
                $row,
                sprintf($this->translator->translate('txt-steering-group-decision-on-%s'), ReviewerService::TYPE_PPR),
                'Please indicate a score for this project'
            );
        }
        $decisionSelectCell = 'B' . $row;
        $displaySheet->getStyle($decisionSelectCell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $displaySheet->getStyle($decisionSelectCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $displaySheet->getStyle($decisionSelectCell)->getAlignment()->setWrapText(true);
        $displaySheet->getStyle($decisionSelectCell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(
            'EFEFEF'
        );
        $displaySheet->getStyle($decisionSelectCell)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BBBBBB');

        $finalScores = EvaluationReport::getVersionScores() + EvaluationReport::getReportScores();

        // Set up conditional style for rejected PPR
        $conditional = new Conditional();
        $conditional->setConditionType(Conditional::CONDITION_CONTAINSTEXT)
            ->setOperatorType(Conditional::OPERATOR_CONTAINSTEXT)
            ->setText($this->translator->translate($finalScores[EvaluationReport::SCORE_REJECTED]));
        $conditional->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getEndColor()->setRGB('F2DCDB');
        $displaySheet->getStyle($decisionSelectCell)->setConditionalStyles([$conditional]);

        if ($this->evaluationReport->getScore() !== null) {
            $finalScore = $this->translator->translate($finalScores[$this->evaluationReport->getScore()]);
            $displaySheet->setCellValue($decisionSelectCell, $finalScore);
        }

        // Add the final score + project status (only for PPR) when not for distribution
        if (!$this->forDistribution) {
            $sheetName = "'" . $this->translator->translate('txt-evaluation-report') . "'";
            $this->parseDropdown($displaySheet, $decisionSelectCell, '=finalScores');
            $dataSheet->setCellValue(
                'F1',
                '=VLOOKUP(' . $sheetName . '!' . $decisionSelectCell . ',LookupData!G1:H' . count($finalScores)
                . ',2,FALSE)'
            );

            // Add project status for PPR
            if ($reportType === EvaluationReport\Type::TYPE_REPORT) {
                $row++;
                $displaySheet->getRowDimension($row)->setRowHeight(20);
                $this->parseCriterionLabel(
                    $displaySheet,
                    $row,
                    $this->translator->translate('txt-project-status'),
                    'Please indicate the general status of the project regardless of the result of PPR evaluation'
                );
                $projectStatusSelectCell = 'B' . $row;
                $displaySheet->getStyle($projectStatusSelectCell)->getAlignment()->setVertical(
                    Alignment::VERTICAL_CENTER
                );
                $displaySheet->getStyle($projectStatusSelectCell)->getAlignment()->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );
                $displaySheet->getStyle($projectStatusSelectCell)->getAlignment()->setWrapText(true);
                $displaySheet->getStyle($projectStatusSelectCell)->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('EFEFEF');
                $displaySheet->getStyle($projectStatusSelectCell)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BBBBBB');

                $projectStatuses = EvaluationReport\ProjectReport::getProjectStatuses();
                if ($this->evaluationReport->getProjectReportReport()->getProjectStatus() !== null) {
                    $projectStatus = $this->translator->translate(
                        $projectStatuses[$this->evaluationReport->getProjectReportReport()->getProjectStatus()]
                    );
                    $displaySheet->setCellValue($projectStatusSelectCell, $projectStatus);
                }
                $this->parseDropdown($displaySheet, $projectStatusSelectCell, '=projectStatuses');
                $dataSheet->setCellValue(
                    'G1',
                    '=VLOOKUP(' . $sheetName . '!' . $projectStatusSelectCell . ',LookupData!J1:K' . count(
                        $projectStatuses
                    ) . ',2,FALSE)'
                );
            }
        }
        $row++;

        // Stg reviewers
        if (!$this->forDistribution) {
            $displaySheet->getRowDimension($row)->setRowHeight(20);
            $this->parseCriterionLabel(
                $displaySheet,
                $row,
                $this->translator->translate('txt-steering-group-reviewers')
            );
            $reviewers = [];
            /** @var VersionReviewer|ReportReviewer $reviewer */
            foreach ($this->evaluationReportService->getReviewers($this->evaluationReport) as $reviewer) {
                $reviewers[] = $reviewer->getContact()->parseFullName();
            }
            $displaySheet->setCellValue('B' . $row, implode(', ', $reviewers));
            $row++;
        }
    }

    private function parseDropdown(Worksheet $displaySheet, string $cell, string $formula): void
    {
        $validation = $displaySheet->getCell($cell)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(false);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle($this->translator->translate('txt-input-error'));
        $validation->setError($this->translator->translate('txt-value-is-not-in-list'));
        $validation->setFormula1($formula);
    }

    private function parseResult(
        Worksheet $displaySheet,
        ?Worksheet $dataSheet,
        Result $result,
        int &$row,
        int &$dataRow
    ): void {
        // Data
        $criterionIdCell = 'A' . $dataRow;
        $resultIdCell = 'B' . $dataRow;
        $scoreCell = 'C' . $dataRow;
        $valueCell = 'D' . $dataRow;
        $commentCell = 'E' . $dataRow;
        // Presentation
        $scoreSelectCell = 'B' . $row;
        $resultCell = 'C' . $row;
        $sheetName = "'" . $this->translator->translate('txt-evaluation-report') . "'";
        $scores = Result::getScoreValues();
        $displaySheet->getRowDimension($row)->setRowHeight(20);

        // Fill the hidden columns, set formulas
        if (!$this->forDistribution) {
            $dataSheet->setCellValue($criterionIdCell, $result->getCriterionVersion()->getId());
            if (!$result->isEmpty()) {
                $dataSheet->setCellValue($resultIdCell, $result->getId());
            }
        }

        // Set criterion etc.
        $this->parseCriterionLabel(
            $displaySheet,
            $row,
            $result->getCriterionVersion()->getCriterion()->getCriterion(),
            $result->getCriterionVersion()->getCriterion()->getHelpBlock()
        );
        $backgroundColor = $result->getCriterionVersion()->getHighlighted() ? 'FDE9D9' : 'EFEFEF';
        $displaySheet->getStyle($scoreSelectCell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $displaySheet->getStyle($scoreSelectCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $displaySheet->getStyle($scoreSelectCell)->getAlignment()->setWrapText(true);
        $displaySheet->getStyle($scoreSelectCell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(
            $backgroundColor
        );

        $displaySheet->getStyle($scoreSelectCell)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BBBBBB');

        // Set the input types
        switch ($result->getCriterionVersion()->getCriterion()->getInputType()) {
            case Criterion::INPUT_TYPE_BOOL:
                if (!$this->forDistribution) {
                    $this->parseDropdown($displaySheet, $scoreSelectCell, '=yesNo');
                    $dataSheet->setCellValue(
                        $valueCell,
                        '=VLOOKUP(' . $sheetName . '!' . $scoreSelectCell . ',LookupData!D1:E2,2,FALSE)'
                    );
                }
                $value = $this->translator->translate('txt-yes');
                if ($result->getValue() === 'No') {
                    $value = $this->translator->translate('txt-no');
                }
                $displaySheet->setCellValue($scoreSelectCell, $value);
                break;
            case Criterion::INPUT_TYPE_SELECT:
                $selectValues = Json::decode($result->getCriterionVersion()->getCriterion()->getValues());
                if (!$this->forDistribution) {
                    $this->parseDropdown(
                        $displaySheet,
                        $scoreSelectCell,
                        '"' . implode(',', $selectValues) . '"'
                    );
                    $dataSheet->setCellValue($valueCell, '=' . $sheetName . '!' . $scoreSelectCell);
                }
                if ($result->getValue() !== null) {
                    $displaySheet->setCellValue($scoreSelectCell, $result->getValue());
                } else {
                    $displaySheet->setCellValue($scoreSelectCell, reset($selectValues));
                }
                break;
        }

        if ($result->getCriterionVersion()->getCriterion()->getHasScore() === true) {
            $displaySheet->getRowDimension($row)->setRowHeight(50);
            if (!$this->forDistribution) {
                $this->parseDropdown($displaySheet, $scoreSelectCell, '=scores');
                $score = $this->translator->translate($scores[$result->getScore()]);
                $displaySheet->setCellValue($scoreSelectCell, $score);
                $dataSheet->setCellValue(
                    $scoreCell,
                    '=VLOOKUP(' . $sheetName . '!' . $scoreSelectCell . ',LookupData!A1:B' . count(
                        Result::getScoreValues()
                    ) . ',2,FALSE)'
                );
                $dataSheet->setCellValue(
                    $commentCell,
                    '=IF(ISBLANK(' . $sheetName . '!' . $resultCell . '),"",' . $sheetName . '!' . $resultCell . ')'
                );
                $displaySheet->getStyle($resultCell)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $displaySheet->getStyle($resultCell)->getAlignment()->setWrapText(true);
                $displaySheet->getStyle($resultCell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(
                    $backgroundColor
                );
                $displaySheet->getStyle($resultCell)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BBBBBB');
                $displaySheet->setCellValue($resultCell, $result->getComment());
            } else {
                $mergeCell = $scoreSelectCell . ':' . $resultCell;
                $displaySheet->mergeCells($mergeCell);
                $displaySheet->getStyle($mergeCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $displaySheet->getStyle($mergeCell)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $displaySheet->getStyle($mergeCell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(
                    $backgroundColor
                );
                $displaySheet->getStyle($resultCell)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BBBBBB');
                $displaySheet->setCellValue($scoreSelectCell, $result->getComment());
            }
        } else {
            if (in_array(
                $result->getCriterionVersion()->getCriterion()->getInputType(),
                [Criterion::INPUT_TYPE_TEXT, Criterion::INPUT_TYPE_STRING],
                true
            )
            ) {
                $mergeCell = $scoreSelectCell . ':' . $resultCell;
                $displaySheet->mergeCells($mergeCell);
                $displaySheet->getStyle($mergeCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $displaySheet->getStyle($mergeCell)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BBBBBB');
                if ($result->getCriterionVersion()->getCriterion()->getInputType() === Criterion::INPUT_TYPE_TEXT) {
                    $displaySheet->getRowDimension($row)->setRowHeight(50);
                    $displaySheet->getStyle($mergeCell)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                }
                if (!$this->forDistribution) {
                    $dataSheet->setCellValue(
                        $valueCell,
                        '=IF(ISBLANK(' . $sheetName . '!' . $scoreSelectCell . '),"",' . $sheetName . '!'
                        . $scoreSelectCell . ')'
                    );
                }
            }
            if ($result->getValue() !== null) {
                $displaySheet->setCellValue($scoreSelectCell, $result->getValue());
            }
        }

        $row++;
        $dataRow++;
    }

    private function parseChartData(
        Worksheet $chartDataSheet,
        Result $result,
        array $allTopics,
        int &$chartDataRow,
        int $dataRow
    ): void {
        $topicCount = count($allTopics);
        $topicWeights = $result->getCriterionVersion()->getVersionTopics()->toArray();
        if (!empty($topicWeights)) {
            // Add topic weight and topic weight score totals
            $column = 'A';
            for ($i = 0; $i < $topicCount; $i++) {
                $chartDataSheet->setCellValue(
                    $column . '2',
                    '=SUM(' . $column . '3:' . $column . $chartDataRow . ')'
                );
                $column++;
            }
            $column++;
            for ($i = 0; $i < $topicCount; $i++) {
                $chartDataSheet->setCellValue(
                    $column . '2',
                    '=SUM(' . $column . '3:' . $column . $chartDataRow . ')'
                );
                $column++;
            }

            // Add chart axis data
            $column++;
            $topicWeightColumn = 'A';
            $topicScoreColumn = $this->getColumnFromIndex($topicCount + 2);
            for ($i = 0; $i < $topicCount; $i++) {
                $chartDataSheet->setCellValue(
                    $column . '2',
                    '=MAX(0,IF(' . $topicWeightColumn . '2,' . $topicScoreColumn . '2/' . $topicWeightColumn . '2,0))'
                );
                $column++;
                $topicWeightColumn++;
                $topicScoreColumn++;
            }

            // Add topic weight per criterion
            $column = 'A';
            /** @var Topic $topic */
            foreach ($allTopics as $topic) {
                $weight = 0;
                /** @var Criterion\VersionTopic $topicWeight */
                foreach ($topicWeights as $key => $topicWeight) {
                    if ($topic->getId() === $topicWeight->getTopic()->getId()) {
                        $weight = $topicWeight->getWeight();
                        unset($topicWeights[$key]);
                        break; // Match, so no further iteration needed
                    }
                }
                $chartDataSheet->setCellValue($column . $chartDataRow, $weight);
                $column++;
            }

            // Add topic scores
            $column++;
            $lookupColumn = 'A';
            for ($i = 0; $i < $topicCount; $i++) {
                $chartDataSheet->setCellValue(
                    $column . $chartDataRow,
                    '=PRODUCT(ImportData!C' . ($dataRow - 1) . ',' . $lookupColumn . $chartDataRow . ')'
                );
                $lookupColumn++;
                $column++;
            }

            $chartDataRow++;
        }
    }

    private function getColumnFromIndex(int $index, string $startColumn = 'A'): string
    {
        $column = $startColumn;
        for ($i = 1; $i < $index; $i++) {
            $column++;
        }

        return $column;
    }

    private function parseRadarChart(array $allTopics): Chart
    {
        $topicCount = count($allTopics);
        $scoreLabels = Result::getScoreValues();
        $scoreValues = array_keys($scoreLabels);
        sort($scoreValues);

        // Define the data series and give it a color
        $dataSeriesLabels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                null,
                null,
                1,
                [$this->translator->translate('txt-evaluation-topic-scores')],
                null,
                'A7D8B8'
            )
        ];

        $rangeColumnStart = 'A';
        $rangeColumnEnd = $this->getColumnFromIndex($topicCount);
        $dataSeriesAxisLabels = [
            new DataSeriesValues(
                'String',
                'ChartData!$' . $rangeColumnStart . '$1:$' . $rangeColumnEnd . '$1',
                null,
                $topicCount
            )
        ];

        $rangeColumnStart = $this->getColumnFromIndex(($topicCount * 2) + 3);
        $rangeColumnEnd = $this->getColumnFromIndex($topicCount, $rangeColumnStart);
        $dataSeriesValues = [
            new DataSeriesValues(
                'Number',
                'ChartData!$' . $rangeColumnStart . '$2:$' . $rangeColumnEnd . '$2',
                null,
                $topicCount
            )
        ];

        $dataSeries = new DataSeries(
            DataSeries::TYPE_RADARCHART,
            null,
            [0],
            $dataSeriesLabels,
            $dataSeriesAxisLabels,
            $dataSeriesValues,
            null,
            null,
            DataSeries::STYLE_FILLED
        );

        // Hide Y axis and set minimum and maximum chart bounds
        $yAxisStyle = new Axis();
        $yAxisStyle->setAxisOptionsProperties(
            'none',
            null,
            null,
            null,
            null,
            null,
            Result::SCORE_NOT_EVALUATED,
            end($scoreValues)
        );

        $chart = new Chart(
            'evaluation_summary',
            new Title('Evaluation summary'),
            null,
            new PlotArea(new Layout(), [$dataSeries]),
            true,
            0,
            null,
            null,
            $yAxisStyle,
            null
        );

        return $chart;
    }

    public function parseResponse(): Response
    {
        $response = new Response();
        if (!($this->excel instanceof Spreadsheet)) {
            return $response->setStatusCode(Response::STATUS_CODE_404);
        }

        /** @var Xlsx $writer */
        $writer = IOFactory::createWriter($this->excel, 'Xlsx');
        $writer->setIncludeCharts(!$this->forDistribution);

        ob_start();
        // Gzip the output when possible. @see http://php.net/manual/en/function.ob-gzhandler.php
        $gzip = ob_start('ob_gzhandler');

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
                'Content-Disposition' => 'attachment; filename="' . $this->parseFileName() . '"',
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Length'      => $contentLength,
                'Expires'             => '0',
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

    /**
     * Parse the filename
     * ITEA file name should be NUMBER_ProjectName_Filetype_(Period)_(evaluation)
     *
     * @return string
     */
    public function parseFileName(): string
    {
        $fileNameTemplate = '%d_%s_%s%s_evaluation.xlsx';
        $project = EvaluationReportService::getProject($this->evaluationReport);
        $typeId = $this->evaluationReportService->parseEvaluationReportType($this->evaluationReport);
        $period = '';
        // Parse the period for project report evaluations
        if ($typeId === EvaluationReport\Type::TYPE_REPORT) {
            $periodTemplate = '_%dH%d';
            $report = $this->evaluationReport->getProjectReportReport()->getReport();
            if ($report === null) {
                $report = $this->evaluationReport->getProjectReportReport()->getReviewer()->getProjectReport();
            }
            $period = sprintf($periodTemplate, $report->getYear(), $report->getSemester());
        }
        // Get the evaluation report type label
        $lookup = [
            EvaluationReport\Type::TYPE_REPORT           => ReviewerService::TYPE_PPR,
            EvaluationReport\Type::TYPE_PO_VERSION       => ReviewerService::TYPE_PO,
            EvaluationReport\Type::TYPE_FPP_VERSION      => ReviewerService::TYPE_FPP,
            EvaluationReport\Type::TYPE_MAJOR_CR_VERSION => ReviewerService::TYPE_CR,
            EvaluationReport\Type::TYPE_MINOR_CR_VERSION => ReviewerService::TYPE_CR
        ];
        $type = ($typeId && isset($lookup[$typeId])) ? $lookup[$typeId] : '';

        return sprintf($fileNameTemplate, $project->getNumber(), $project->getProject(), $type, $period);
    }

    public function getExcel(): Spreadsheet
    {
        return $this->excel;
    }
}
