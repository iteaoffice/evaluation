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

use DateTime;
use JpGraph\JpGraph;
use LinearScale;
use Project\Entity\Challenge;
use Evaluation\Controller\Plugin\ReportPdf;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Entity\Report\Criterion;
use Evaluation\Entity\Report\Result;
use Evaluation\Service\EvaluationReportService;
use Evaluation\Options\ModuleOptions;
use Project\Entity\Rationale;
use Project\Entity\Report\Reviewer as ReportReviewer;
use Project\Entity\Version\Reviewer as VersionReviewer;
use Project\Entity\Version\Version;
use Project\Service\ProjectService;
use Project\Service\VersionService;
use RadarAxis;
use RadarGraph;
use RadarPlot;
use setasign\Fpdi\Tcpdf\Fpdi as TcpdfFpdi;
use Zend\Http\Headers;
use Zend\Http\Response;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use function array_map;
use function array_reverse;
use function array_slice;
use function array_sum;
use function array_unshift;
use function ceil;
use function define;
use function end;
use function imagepng;
use function implode;
use function in_array;
use function json_decode;
use function key;
use function number_format;
use function ob_end_flush;
use function ob_get_clean;
use function ob_get_length;
use function ob_start;
use function realpath;
use function reset;
use function sprintf;

/**
 * Class PdfExport
 * @package Evaluation\Controller\Plugin\Report
 */
final class PdfExport extends AbstractPlugin
{
    /**
     * @var array
     */
    private static $colWidths = [1 => 80, 2 => 45, 3 => 150];

    /**
     * @var int
     */
    private static $lineHeights = [
        'category' => 8, 'type' => 6, 'line' => 5, 'bigLine' => 15
    ];

    /**
     * @var int
     */
    private static $topMargin = 25;

    /**
     * @var int
     */
    private static $bottomMargin = 10;

    /**
     * @var string
     */
    private static $orientation = 'L';

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
     * @var TcpdfFpdi
     */
    private $pdf;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var bool
     */
    private $forDistribution = false;

    /**
     * @var bool
     */
    private $showGraphAndScores = false;

    /**
     * @var array
     */
    private $results = [];

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

    public function __invoke(EvaluationReport $evaluationReport, bool $forDistribution = false): PdfExport
    {
        $this->evaluationReport   = $evaluationReport;
        $this->forDistribution    = $forDistribution;
        $reportType               = $this->evaluationReportService->parseEvaluationReportType($evaluationReport);
        $this->showGraphAndScores = (!$this->forDistribution || ($reportType === EvaluationReport\Type::TYPE_REPORT));
        $this->results            = $this->evaluationReportService->getSortedResults($evaluationReport);

        $pdf = new ReportPdf();
        $pdf->setTemplate($this->moduleOptions->getReportTemplate());

        //@todo Change this so the template is taken from the program
        if (defined('ITEAOFFICE_HOST') && ITEAOFFICE_HOST === 'aeneas') {
            $originalTemplate = $this->moduleOptions->getReportTemplate();
            $project          = EvaluationReportService::getProject($this->evaluationReport);

            $template = $originalTemplate;
            if (in_array('Penta', $project->parsePrograms(), true)) {
                $template = str_replace(
                    'evaluation-report-template',
                    'evaluation-report-template.penta',
                    $originalTemplate
                );
            }

            if (in_array('EURIPIDES', $project->parsePrograms(), true)) {
                $template = str_replace(
                    'evaluation-report-template',
                    'evaluation-report-template.euripides',
                    $originalTemplate
                );
            }

            if (in_array('Penta', $project->parsePrograms(), true)
                && in_array('EURIPIDES', $project->parsePrograms(), true)
            ) {
                $template = str_replace(
                    'evaluation-report-template',
                    'evaluation-report-template.penta-euripides',
                    $originalTemplate
                );
            }

            $pdf->setTemplate($template);
        }

        $pdf->SetFontSize(9);
        $pdf->SetTopMargin(self::$topMargin);
        $pdf->setFooterMargin(0);
        $pdf->SetDisplayMode('real');
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->SetAuthor($this->moduleOptions->getReportAuthor());
        $title = sprintf(
            $this->translator->translate('txt-final-evaluation-report-for-%s'),
            EvaluationReportService::parseLabel($evaluationReport)
        );
        $pdf->setTitle($title);
        $this->fileName = $title . '.pdf';
        $this->pdf      = $pdf;
        $this->pdf->AddPage(self::$orientation);

        // Add the results
        $margins = $this->pdf->getMargins();
        $this->pdf->SetY($margins['top']);

        $categoryCount   = 1;
        $firstType       = true;
        $currentCategory = '';
        $currentType     = '';

        // No STG decision in export PO/FPP evaluation
        $hideDetailsFor = [EvaluationReport\Type::TYPE_PO_VERSION, EvaluationReport\Type::TYPE_FPP_VERSION];
        $showDetails    = (!$this->forDistribution || !in_array($reportType, $hideDetailsFor, true));

        /** @var Result $result */
        foreach ($this->results as $result) {
            /** @var Criterion\Type $type */
            $type     = $result->getCriterionVersion()->getType();
            /** @var Criterion\Category $category */
            $category = $type->getCategory();

            if ($category->getCategory() !== $currentCategory) {
                $this->parseCategory($category->getCategory());
                if ($categoryCount === 1) {
                    if ($this->showGraphAndScores) {
                        $this->parseRadarChart();
                    }
                    $this->parseType($this->translator->translate('txt-project-details'), $this->showGraphAndScores);
                    $this->parseProjectData();
                    if ($showDetails) {
                        $this->parseType($this->translator->translate('txt-review-details'), $this->showGraphAndScores);
                        $this->parseSteeringGroupData();
                    }
                }
                $currentCategory = $category->getCategory();
            }

            $confidentialType = $this->evaluationReportService->typeIsConfidential(
                $result->getCriterionVersion()->getType(),
                $this->evaluationReport->getVersion()
            );
            if (($type->getType() !== $currentType) && !$confidentialType) {
                // Only a short header when no details are shown and it's the first one
                $this->parseType($type->getType(), ($firstType && $this->showGraphAndScores && !$showDetails));
                $currentType = $type->getType();
                $firstType   = false;
            }

            if (!$result->getCriterionVersion()->getConfidential()) {
                $this->parseResult($result);
            }

            $categoryCount++;
        }

        return $this;
    }

    private function parseCategory(string $label): void
    {
        $this->pdf->Ln(1);
        $lineHeight = self::$lineHeights['category'];
        $this->checkPageEnding($lineHeight + self::$lineHeights['type'] + self::$lineHeights['bigLine']);
        $this->pdf->Ln(1);
        $borders = [
            'LR' => ['width' => 0.4, 'cap' => 'square', 'join' => 'miter', 'dash' => 0, 'color' => [167, 216, 184]],
            'TB' => ['width' => 0.4, 'cap' => 'square', 'join' => 'miter', 'dash' => 0, 'color' => [0, 0, 0]],
        ];
        $this->pdf->SetFillColor(167, 216, 184);
        $this->pdf->Cell(
            array_sum(self::$colWidths),
            $lineHeight,
            $label,
            $borders,
            1,
            'C',
            true
        );
    }

    private function checkPageEnding(int $lineHeight): void
    {
        if (($this->pdf->GetY() + $lineHeight) >= (210 - self::$bottomMargin)) {
            $this->pdf->AddPage(self::$orientation);
        }
    }

    private function parseRadarChart(): void
    {
        // Generate the chart data
        $allTopics      = $this->evaluationReport->getVersion()->getTopics()->toArray();
        $labels         = [];
        $topicWeights   = [];
        $topicScores    = [];
        $maxTopicWeight = 0;
        $chartData      = [];
        $results        = $this->results; // Work on a copy to not disturb the main iteration

        // Excel plots the radar axes clock-wise and JpGraph counter-clockwise, so make it look clockwise here
        $firstTopic = reset($allTopics);
        $topics     = array_reverse(array_slice($allTopics, 1));
        array_unshift($topics, $firstTopic);

        /** @var Criterion\Topic $topic */
        foreach ($topics as $topic) {
            $labels[]                      = $topic->getTopic();
            $topicWeights[$topic->getId()] = 0;
            $topicScores[$topic->getId()]  = 0;
        }

        /** @var Result $result */
        foreach ($results as $key => $result) {
            foreach ($result->getCriterionVersion()->getVersionTopics() as $criterionTopic) {
                $topicWeights[$criterionTopic->getTopic()->getId()] += $criterionTopic->getWeight();
                if ($criterionTopic->getWeight() > $maxTopicWeight) {
                    $maxTopicWeight = $criterionTopic->getWeight();
                }
                if ($result->getScore() > 0) { // Exclude the negative not evaluated yet scores
                    $topicScores[$criterionTopic->getTopic()->getId()]
                        += ($result->getScore() * $criterionTopic->getWeight());
                }
            }
        }

        $scores = Result::getScoreValues();
        end($scores); // Set pointer to the max score
        foreach ($topicWeights as $topicId => $totalWeight) {
            $chartData[] = ($totalWeight === 0)
                ? 0
                : (($topicScores[$topicId] / ($totalWeight * key($scores))) * $maxTopicWeight);
        }

        // Create the chart
        define('TTF_DIR', realpath($_SERVER['DOCUMENT_ROOT'] . '../styles/itea/font'));
        JpGraph::load();
        JpGraph::module('radar');
        $chart = new RadarGraph(560, 360);
        $chart->SetUserFont('/FreeSans.ttf');
        $chart->SetColor('white');
        $chart->SetScale('lin', 0, $maxTopicWeight);
        $chart->SetTitles($labels);
        $chart->SetCenter(0.5, 0.57);
        $chart->SetShadow(false);
        $chart->ShowMinorTickMarks(true);

        $chart->img->SetAntiAliasing(true);
        /** @var LinearScale $yScale */
        $yScale = $chart->yscale;
        $yScale->ticks->Set(1, (($maxTopicWeight === 1) ? 0.2 : 0.5));
        $chart->title->SetFont(FF_USERFONT, FS_NORMAL, 16);
        $chart->title->Set($this->translator->translate('txt-evaluation-topic-scores'));
        /** @var RadarAxis $axis */
        $axis = $chart->axis;
        $axis->SetFont(FF_USERFONT, FS_NORMAL, 12);
        $axis->title->SetFont(FF_USERFONT, FS_NORMAL, 14);

        $plot = new RadarPlot($chartData);
        $plot->SetFillColor([167, 216, 184]);
        $chart->Add($plot);

        // Get the image binary string from the output buffer
        ob_start();
        imagepng($chart->Stroke(_IMG_HANDLER));
        $image = ob_get_clean();

        // Add the image to the pdf
        $xPos = self::$colWidths[1] + (2 * self::$colWidths[2]) + 15;
        $this->pdf->Image('@' . $image, $xPos, $this->pdf->GetY() + 2, 100, 60);
    }

    private function parseType(string $label, $short = false): void
    {
        $this->pdf->Ln(1);
        $lineHeight = self::$lineHeights['type'];
        $this->checkPageEnding($lineHeight + self::$lineHeights['bigLine']);
        $this->pdf->Ln(1);
        $borders = [
            'LR' => ['width' => 0.4, 'cap' => 'square', 'join' => 'miter', 'dash' => 0, 'color' => [221, 221, 221]],
            'TB' => ['width' => 0.4, 'cap' => 'square', 'join' => 'miter', 'dash' => 0, 'color' => [0, 0, 0]],
        ];
        $width = $short ? (self::$colWidths[1] + (2 * self::$colWidths[2])) : array_sum(self::$colWidths);
        $this->pdf->SetFillColor(221, 221, 221);
        $this->pdf->SetFont(ReportPdf::DEFAULT_FONT, 'I');
        $this->pdf->Cell(
            $width,
            $lineHeight,
            $label,
            $borders,
            1,
            'L',
            true
        );
        $this->pdf->SetFont(ReportPdf::DEFAULT_FONT, 'N');
        $this->pdf->Ln(1);
    }

    private function parseProjectData(): void
    {
        $lineHeight       = self::$lineHeights['line'];
        $thirdColumnWidth = !$this->showGraphAndScores ? self::$colWidths[3] : self::$colWidths[2];

        // Project name + report/version
        $project = $this->evaluationReportService->getProject($this->evaluationReport);
        $this->parseCriterionLabel($this->translator->translate('txt-project-name'));
        $this->pdf->Cell(self::$colWidths[2], $lineHeight, $project->parseFullName());
        $reportOrVersion = $this->evaluationReportService->parseLabel($this->evaluationReport, '%3$s');
        $this->pdf->MultiCell(
            $thirdColumnWidth,
            $lineHeight,
            $reportOrVersion,
            0,
            'L',
            false,
            1,
            '',
            '',
            true,
            0,
            false,
            true,
            0,
            'M'
        );

        // Project title
        $cellWidth = (self::$colWidths[2] + $thirdColumnWidth);
        $cellHeight = $this->getCellHeight($lineHeight, $cellWidth, $project->getTitle());
        $this->parseCriterionLabel($this->translator->translate('txt-project-title'));
        $this->pdf->MultiCell(
            $cellWidth,
            $cellHeight,
            $project->getTitle(),
            0,
            'L',
            false,
            1,
            '',
            '',
            true,
            0,
            false,
            true,
            $cellHeight,
            'M'
        );

        // Project leader
        $this->parseCriterionLabel($this->translator->translate('txt-project-leader'));
        $this->pdf->Cell(self::$colWidths[2], $lineHeight, $project->getContact()->parseFullName());
        $organisation = $project->getContact()->getContactOrganisation()->getOrganisation();
        // Organisation
        $organisationLabel = sprintf(
            '%s, %s',
            $organisation->getOrganisation(),
            $organisation->getCountry()->getCountry()
        );
        $cellHeight = $this->getCellHeight($lineHeight, $thirdColumnWidth, $organisationLabel);
        $this->pdf->MultiCell(
            $thirdColumnWidth,
            $cellHeight,
            $organisationLabel,
            0,
            'L',
            false,
            1,
            '',
            '',
            true,
            0,
            false,
            true,
            $cellHeight,
            'M'
        );

        // Latest project version (only for PPR)
        if ($this->evaluationReport->getProjectReportReport() !== null) {
            $this->parseCriterionLabel($this->translator->translate('txt-latest-project-version'));
            $latestVersion = $this->projectService->getLatestApprovedProjectVersion($project);

            if (null !== $latestVersion) {
                $this->pdf->Cell(self::$colWidths[2], $lineHeight, $latestVersion->getVersionType()->getDescription());

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
                        : "\n(" . $action . ')');
                $this->pdf->MultiCell(
                    $thirdColumnWidth,
                    $lineHeight,
                    $label,
                    0,
                    'L',
                    false,
                    1,
                    '',
                    '',
                    true,
                    0,
                    false,
                    true,
                    0,
                    'M'
                );
            }
        }

        // Project size
        $version = new Version();
        $projectVersionReport = $this->evaluationReport->getProjectVersionReport();

        if ($projectVersionReport !== null) {
            if ($projectVersionReport->getReviewer() instanceof VersionReviewer) {
                $version = $projectVersionReport->getReviewer()->getVersion();
            } elseif ($projectVersionReport->getVersion() instanceof Version) {
                $version = $projectVersionReport->getVersion();
            }
        } else {
            $version = $this->projectService->getLatestApprovedProjectVersion($project);
        }
        $this->parseCriterionLabel($this->translator->translate('txt-project-size'));
        $this->pdf->Cell(
            self::$colWidths[2],
            $lineHeight,
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
        $this->pdf->Cell(
            $thirdColumnWidth,
            $lineHeight,
            sprintf(
                '%s: %s PY',
                $this->translator->translate('txt-effort'),
                number_format(
                    $this->versionService->findTotalEffortVersionByProjectVersion($version),
                    2,
                    '.',
                    ','
                )
            ),
            0,
            1
        );

        // Project start and end date
        $this->parseCriterionLabel($this->translator->translate('txt-project-start-and-end-date'));
        $startDate = $this->projectService->parseOfficialDateStart($project);
        $endDate = $this->projectService->parseOfficialDateEnd($project);
        if ($startDate instanceof DateTime) {
            $ln = ($endDate instanceof DateTime) ? 0 : 1;
            $this->pdf->Cell(self::$colWidths[2], $lineHeight, $startDate->format('j M Y'), 0, $ln);
        }
        if ($endDate instanceof DateTime) {
            $this->pdf->Cell($thirdColumnWidth, $lineHeight, $endDate->format('j M Y'), 0, 1);
        }

        // Consortium
        $this->parseCriterionLabel($this->translator->translate('txt-consortium'));
        $countries  = implode(', ', array_map(
            function (Rationale $rationale) {
                return $rationale->getCountry()->getCountry();
            },
            $project->getRationale()->toArray()
        ));
        $cellWidth  = (self::$colWidths[2] + $thirdColumnWidth);
        $cellHeight = $this->getCellHeight($lineHeight, $cellWidth, $countries);
        $this->pdf->MultiCell(
            $cellWidth,
            $cellHeight,
            $countries,
            0,
            'L',
            false,
            1,
            '',
            '',
            true,
            0,
            false,
            true,
            $cellHeight,
            'M'
        );

        // Challenge
        $this->parseCriterionLabel($this->translator->translate('txt-challenge'));
        $challenges = array_map(
            function (Challenge $challenge) {
                return $challenge->getChallenge();
            },
            $project->getProjectChallenge()->toArray()
        );

        $this->pdf->Cell(
            self::$colWidths[2] + $thirdColumnWidth,
            $lineHeight,
            implode(', ', $challenges),
            0,
            1
        );

        $this->pdf->Ln(1);
    }

    private function parseCriterionLabel(string $criterionLabel, int $lineHeight = null): void
    {
        if ($lineHeight === null) {
            $lineHeight = self::$lineHeights['line'];
        }
        $this->checkPageEnding($lineHeight);
        $this->pdf->SetFont(ReportPdf::DEFAULT_FONT, 'B');
        $this->pdf->MultiCell(
            self::$colWidths[1],
            $lineHeight,
            $criterionLabel,
            0,
            'R',
            false,
            0,
            '',
            '',
            true,
            0,
            false,
            true,
            $lineHeight,
            'M'
        );
        $this->pdf->SetFont(ReportPdf::DEFAULT_FONT, 'N');
    }

    private function getCellHeight(int $lineHeight, int $width, string $content): int
    {
        $stringWidth = $this->pdf->GetStringWidth($content);
        if ($stringWidth > $width) {
            $lines = (int)ceil($stringWidth / $width);
            return (int)(($lines * $lineHeight) - ($lines - 1));
        }
        return $lineHeight;
    }

    private function parseSteeringGroupData(): void
    {
        // Stg decision
        $this->parseCriterionLabel($this->translator->translate('txt-steering-group-decision'));
        $finalScores = EvaluationReport::getVersionScores() + EvaluationReport::getReportScores();

        $finalScore = '';
        $fillColor  = [239, 239, 239]; // Light grey
        if ($this->evaluationReport->getScore() !== null) {
            $finalScore = $this->translator->translate($finalScores[$this->evaluationReport->getScore()]);
            // Red color for rejected PPR
            if ($this->evaluationReport->getScore() === EvaluationReport::SCORE_REJECTED) {
                $fillColor = [242, 220, 219];
            }
        }
        $this->parseContentField($finalScore, $fillColor);

        // Stg reviewers
        if (!$this->forDistribution) {
            $reviewers = [];
            $this->parseCriterionLabel($this->translator->translate('txt-steering-group-reviewers'));
            /** @var VersionReviewer|ReportReviewer $reviewer */
            foreach ($this->evaluationReportService->getReviewers($this->evaluationReport) as $reviewer) {
                $reviewers[] = $reviewer->getContact()->parseFullName();
            }
            $this->pdf->MultiCell(
                self::$colWidths[2] + self::$colWidths[3],
                self::$lineHeights['line'],
                implode(', ', $reviewers),
                0,
                'L',
                false,
                1,
                '',
                '',
                true,
                0,
                false,
                true,
                self::$lineHeights['line'],
                'M',
                true
            );
        }
    }

    private function parseContentField(string $content, array $fillColor, int $height = null, int $score = null): void
    {
        $this->pdf->SetFillColor(...$fillColor);
        $borders = [
            'LRTB' => ['width' => 0.3, 'cap' => 'square', 'join' => 'miter', 'dash' => 0, 'color' => [187, 187, 187]],
        ];
        $commentWidth = ($height > self::$lineHeights['line'])
            ? (self::$colWidths[2] + self::$colWidths[3]) : self::$colWidths[2];

        if ($height === null) {
            $height = self::$lineHeights['line'];
        }

        if ($score !== null) {
            $commentWidth = self::$colWidths[3];
            $scoreValues = Result::getScoreValues();
            $this->pdf->MultiCell(
                self::$colWidths[2],
                $height,
                $this->translator->translate($scoreValues[$score]),
                $borders,
                'L',
                true,
                0,
                '',
                '',
                true,
                0,
                false,
                true,
                $height,
                'M',
                true
            );
        }
        $this->pdf->MultiCell(
            $commentWidth,
            $height,
            $content,
            $borders,
            'L',
            true,
            1,
            '',
            '',
            true,
            0,
            false,
            true,
            $height,
            'M',
            true
        );
    }

    private function parseResult(Result $result): void
    {
        $isNew = $result->isEmpty();

        // Set criterion etc.
        $fillColor = $result->getCriterionVersion()->getHighlighted() ? [253, 233, 217] : [239, 239, 239];

        // Set the input types
        switch ($result->getCriterionVersion()->getCriterion()->getInputType()) {
            case Criterion::INPUT_TYPE_BOOL:
                $this->parseCriterionLabel($result->getCriterionVersion()->getCriterion()->getCriterion());
                $value = $this->translator->translate('txt-yes');
                if (!$isNew && ($result->getValue() === 'No')) {
                    $value = $this->translator->translate('txt-no');
                }
                $this->parseContentField($value, $fillColor);
                break;

            case Criterion::INPUT_TYPE_SELECT:
                $this->parseCriterionLabel($result->getCriterionVersion()->getCriterion()->getCriterion());
                $selectValues = json_decode($result->getCriterionVersion()->getCriterion()->getValues(), true);
                if (!$isNew && null !== $result->getValue()) {
                    $this->parseContentField($result->getValue(), $fillColor);
                } else {
                    $this->parseContentField(reset($selectValues), $fillColor);
                }
                break;

            default:
                $this->parseCriterionRow($result, $fillColor);
        }
    }

    private function parseCriterionRow(Result $result, array $fillColor): void
    {
        $big = $hasScore = $result->getCriterionVersion()->getCriterion()->getHasScore();
        if (!$big && ($result->getCriterionVersion()->getCriterion()->getInputType() === Criterion::INPUT_TYPE_TEXT)) {
            $big = true;
        }

        $content    = $hasScore ? (string)$result->getComment() : (string)$result->getValue();
        $lineHeight = $big ? self::$lineHeights['bigLine'] : self::$lineHeights['line'];
        $width      = $big ? (self::$colWidths[2] + self::$colWidths[3]) : self::$colWidths[2];

        $calcLineHeight = $this->pdf->getStringHeight($width, $content);
        if ($calcLineHeight > $lineHeight) {
            $lineHeight = (int)ceil($calcLineHeight);
        }

        $this->checkPageEnding($lineHeight);
        $this->parseCriterionLabel($result->getCriterionVersion()->getCriterion()->getCriterion(), $lineHeight);
        // Scores only get added internal reports
        if ($hasScore && $this->showGraphAndScores) {
            $this->parseContentField($content, $fillColor, $lineHeight, $result->getScore());
        } else {
            $this->parseContentField($content, $fillColor, $lineHeight);
        }
    }

    public function parseResponse(): Response
    {
        $response = new Response();
        if (!($this->pdf instanceof TcpdfFpdi)) {
            return $response->setStatusCode(Response::STATUS_CODE_404);
        }

        ob_start();
        // Gzip the output when possible. @see http://php.net/manual/en/function.ob-gzhandler.php
        $gzip = ob_start('ob_gzhandler');
        echo $this->pdf->Output('', 'S');
        if ($gzip) {
            ob_end_flush(); // Flush the gzipped buffer into the main buffer
        }
        $contentLength = ob_get_length();

        // Prepare the response
        $response->setContent(ob_get_clean());
        $response->setStatusCode(Response::STATUS_CODE_200);
        $headers = new Headers();
        $headers->addHeaders([
            'Content-Disposition' => 'attachment; filename="' . $this->fileName . '"',
            'Content-Type'        => 'application/pdf',
            'Content-Length'      => $contentLength,
            'Expires'             => '0',
            'Cache-Control'       => 'must-revalidate',
            'Pragma'              => 'public',
        ]);
        if ($gzip) {
            $headers->addHeaders(['Content-Encoding' => 'gzip']);
        }
        $response->setHeaders($headers);

        return $response;
    }

    public function getPdf(): TcpdfFpdi
    {
        return $this->pdf;
    }
}
