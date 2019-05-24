<?php

/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @category    Project
 *
 * @author      Bart van Eijck <bart.van.eijck@itea3.org>
 * @copyright   Copyright (c) 2004-2017 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\Controller\Plugin\Report;

use Affiliation\Service\AffiliationService;

use DateTime;
use General\Service\CountryService;
use Project\Controller\Plugin\CreateEvaluation;
use Project\Entity\Challenge;
use Evaluation\Entity\Evaluation;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Entity\Report\Criterion;
use Evaluation\Entity\Report\Result;
use Evaluation\Entity\Type as EvaluationType;
use Project\Entity\Rationale;
use Project\Entity\Report\Review as ReportReviewer;
use Project\Entity\Version\Review as VersionReviewer;
use Project\Entity\Version\Type;
use Project\Entity\Version\Version;
use Project\Form\MatrixFilter;
use Project\Options\ModuleOptions;
use Evaluation\Service\EvaluationReportService;
use Evaluation\Service\EvaluationService;
use Project\Service\ProjectService;
use Project\Service\VersionService;
use setasign\Fpdi\Tcpdf\Fpdi as TcpdfFpdi;
use Zend\Http\Headers;
use Zend\Http\Response;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Mvc\Controller\PluginManager;
use ZfcTwig\View\TwigRenderer;
use Zend\Json\Json;
use function array_map;
use function array_sum;
use function ceil;
use function implode;
use function number_format;
use function sprintf;
use function in_array;
use function ob_end_flush;
use function ob_get_clean;
use function ob_get_length;
use function ob_start;

/**
 * Class ReportConsolidatedPdfExport
 * @package Evaluation\Controller\Plugin
 */
final class ConsolidatedPdfExport extends AbstractPlugin
{
    private static $colWidths    = [1 => 60, 2 => 60, 3 => 60];
    private static $lineHeights  = ['category' => 8, 'type' => 6, 'line' => 5, 'bigLine' => 15];
    private static $topMargin    = (ITEAOFFICE_HOST === 'itea') ? 30 : 70;
    private static $bottomMargin = (ITEAOFFICE_HOST === 'itea') ? 30 : 20;
    private static $orientation  = 'P';
    private static $fontSize     = 9;
    private static $mainColor    = (ITEAOFFICE_HOST === 'itea') ? [0, 166, 81] : [142, 198, 80];
    private static $subColor     = (ITEAOFFICE_HOST === 'itea') ? [128, 130, 133] : [8, 118, 183];
    private static $gray         = [151, 151, 151];

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
     * @var EvaluationService
     */
    private $evaluationService;

    /**
     * @var AffiliationService
     */
    private $affiliationService;

    /**
     * @var CountryService
     */
    private $countryService;

    /**
     * @var ModuleOptions
     */
    private $moduleOptions;

    /**
     * @var EvaluationReport
     */
    private $evaluationReport;

    /**
     * @var TwigRenderer
     */
    private $renderer;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var PluginManager
     */
    private $controllerPluginManager;

    /**
     * @var TcpdfFpdi
     */
    private $pdf;

    /**
     * @var string
     */
    private $fileName;

    private $forDistribution = false;

    private $results = [];

    public function __construct(
        EvaluationReportService $evaluationReportService,
        ProjectService          $projectService,
        VersionService          $versionService,
        EvaluationService       $evaluationService,
        AffiliationService      $affiliationService,
        CountryService          $countryService,
        ModuleOptions           $moduleOptions,
        PluginManager           $controllerPluginManager,
        TwigRenderer            $renderer,
        TranslatorInterface     $translator
    ) {
        $this->evaluationReportService = $evaluationReportService;
        $this->projectService          = $projectService;
        $this->versionService          = $versionService;
        $this->evaluationService       = $evaluationService;
        $this->affiliationService      = $affiliationService;
        $this->countryService          = $countryService;
        $this->moduleOptions           = $moduleOptions;
        $this->controllerPluginManager = $controllerPluginManager;
        $this->renderer                = $renderer;
        $this->translator              = $translator;
    }

    public function __invoke(EvaluationReport $evaluationReport, bool $forDistribution = false): self
    {
        $this->evaluationReport = $evaluationReport;
        $this->forDistribution  = $forDistribution;
        $this->results          = $this->evaluationReportService->getSortedResults($evaluationReport);

        $pdf = new ReportPdf();
        //Doe some nasty hardcoded stuff for AENEAS
        $pdf->setTemplate($this->moduleOptions->getEvaluationProjectTemplate());

        //@todo Change this so the template is taken from the program
        if (defined('ITEAOFFICE_HOST') && ITEAOFFICE_HOST === 'aeneas') {
            $project          = $this->evaluationReportService->getProject($this->evaluationReport);
            $originalTemplate = $this->moduleOptions->getEvaluationProjectTemplate();

            $template = $originalTemplate;
            if (in_array('Penta', $project->parsePrograms(), true)) {
                $template = str_replace('blank-template-firstpage', 'penta-template', $originalTemplate);
            }

            if (in_array('EURIPIDES', $project->parsePrograms(), true)) {
                $template = str_replace('blank-template-firstpage', 'euripides-template', $originalTemplate);
            }

            if (in_array('Penta', $project->parsePrograms(), true)
                && in_array('EURIPIDES', $project->parsePrograms(), true)
            ) {
                $template = str_replace('blank-template-firstpage', 'penta-euripides-template', $originalTemplate);
            }

            $pdf->setTemplate($template);
        }

        $pdf->SetFontSize(self::$fontSize);
        $pdf->SetTopMargin(self::$topMargin);
        $pdf->setFooterMargin(0);
        $pdf->SetDisplayMode('real');
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->SetAuthor($this->moduleOptions->getEvaluationReportAuthor());
        $title = sprintf(
            $this->translator->translate('txt-consolidated-feedback-for-%s'),
            $this->evaluationReportService->parseLabel($evaluationReport)
        );
        $pdf->setTitle($title);
        $this->fileName = $title . '.pdf';
        $this->pdf = $pdf;
        $this->pdf->AddPage(self::$orientation);

        // Add the results
        $margins = $this->pdf->getMargins();
        $this->pdf->SetY($margins['top']);
        $categoryCount = 1;
        $currentCategory = '';
        $currentType = '';

        $this->parseHeader();

        /** @var Result $result */
        foreach ($this->results as $result) {
            /** @var Criterion\Type $type */
            $type = $result->getCriterion()->getType();
            $category = $type->getCategory();

            if ($category->getCategory() !== $currentCategory) {
                if ($categoryCount === 1) {
                    $this->parseHeading($this->translator->translate('txt-project-details'), 15, 'L', 'sub');

                    $this->parseProjectData();
                    // No STG decision in export PO/FPP evaluation
                    $hideFor = [EvaluationReport\Type::TYPE_PO_VERSION, EvaluationReport\Type::TYPE_FPP_VERSION];
                    $reportType = $this->evaluationReportService->parseEvaluationReportType($evaluationReport);
                    if (!$this->forDistribution || !in_array($reportType, $hideFor, false)) {
                        $this->parseHeading($this->translator->translate('txt-review-details'), 15, 'L', 'sub');
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
                $this->parseHeading($type->getType(), 12, 'L', 'gray');

                $currentType = $type->getType();
            }

            if (!$result->getCriterion()->getConfidential()) {
                $this->parseResult($result);
            }

            $categoryCount++;
        }

        $this->parseHeading($this->translator->translate('txt-public-authorities-feedback'), 15, 'L', 'sub');
        $this->parseFunderFeedbackOverview();

        return $this;
    }

    private function parseHeader(): void
    {
        $project = $this->evaluationReportService->getProject($this->evaluationReport);

        $this->parseHeading($project->parseCalls(), 20);
        $this->parseHeading($this->translator->translate('txt-po-fpp-integrated-report'), 15);


        // Some explansion
        $lineHeight       = self::$lineHeights['line'];
        $thirdColumnWidth = $this->forDistribution ? self::$colWidths[3] : self::$colWidths[2];
        $cellWidth        = (self::$colWidths[2] + $thirdColumnWidth);
        $cellHeight       = $this->getCellHeight($lineHeight, $cellWidth, $this->translator->translate('txt-po-fpp-integrated-report-explanation'));

        $this->pdf->MultiCell(
            $cellWidth,
            $cellHeight,
            $this->translator->translate('txt-po-fpp-integrated-report-explanation'),
            0,
            'C',
            false,
            1,
            40,
            '',
            true,
            0,
            true,
            true,
            $cellHeight,
            'M'
        );

        $this->parseHeading($project->parseFullName(), 20, 'L', 'main');
        $this->parseHeading($project->getTitle(), 12, 'L', 'gray');
    }

    private function parseHeading(string $label, int $size = 10, string $align = 'C', $color = 'black'): void
    {
        $lineHeight = self::$lineHeights['category'];
        $this->checkPageEnding($lineHeight + self::$lineHeights['type'] + self::$lineHeights['bigLine']);

        $this->pdf->SetTextColor(0, 0, 0);

        switch ($color) {
            case 'main':
                $this->pdf->SetTextColor(...self::$mainColor);
                break;
            case 'sub':
                $this->pdf->SetTextColor(...self::$subColor);
                break;
            case 'gray':
                $this->pdf->SetTextColor(...self::$gray);
                break;
            default:
                $this->pdf->SetTextColor(0, 0, 0);
        }

        $this->pdf->SetFontSize($size);
        $this->pdf->Cell(
            array_sum(self::$colWidths),
            $lineHeight,
            $label,
            [],
            1,
            $align
        );
        $this->pdf->SetFontSize(self::$fontSize);
        $this->pdf->SetTextColor(0, 0, 0);
    }

    private function checkPageEnding(int $lineHeight): void
    {
        if (($this->pdf->GetY() + $lineHeight) >= (210 - self::$bottomMargin)) {
            $this->pdf->AddPage(self::$orientation);
        }
    }

    private function parseProjectData(): void
    {
        $lineHeight       = self::$lineHeights['line'];
        $thirdColumnWidth = $this->forDistribution ? self::$colWidths[3] : self::$colWidths[2];

        // Project name + report/version
        $project = $this->evaluationReportService->getProject($this->evaluationReport);
        $this->parseCriterionLabel($this->translator->translate('txt-project-name'));
        $this->pdf->Cell(self::$colWidths[2], $lineHeight, $project->parseFullName());
        $reportOrVersion = $this->evaluationReportService->parseLabel($this->evaluationReport, '%3$s');
        $this->pdf->Cell($thirdColumnWidth, $lineHeight, $reportOrVersion, 0, 1);

        // Project title
        $cellWidth  = (self::$colWidths[2] + $thirdColumnWidth);
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

        // Project size
        $version              = new Version();
        $projectVersionReport = $this->evaluationReport->getProjectVersionReport();

        if ($projectVersionReport !== null) {
            if ($projectVersionReport->getReviewer() instanceof VersionReviewer) {
                $version = $projectVersionReport->getReviewer()->getVersion();
            } elseif ($projectVersionReport->getVersion() instanceof Version) {
                $version = $projectVersionReport->getVersion();
            }
        } else {
            $version = $this->projectService->getLatestProjectVersion($project);
        }
        $this->parseCriterionLabel($this->translator->translate('txt-project-size'));
        $this->pdf->Cell(
            self::$colWidths[2],
            $lineHeight,
            sprintf(
                '%s: %s k€',
                $this->translator->translate('txt-cost'),
                number_format(
                    $this->versionService->findTotalCostVersionByProjectVersion($version) / 1000
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
                    2
                )
            ),
            0,
            1
        );

        // Project start and end date
        $this->parseCriterionLabel($this->translator->translate('txt-project-start-and-end-date'));
        $startDate = $this->projectService->parseOfficialDateStart($project);
        $endDate   = $this->projectService->parseOfficialDateEnd($project);
        if ($startDate instanceof DateTime) {
            $ln = ($endDate instanceof DateTime) ? 0 : 1;
            $this->pdf->Cell(self::$colWidths[2], $lineHeight, $startDate->format('j M Y'), 0, $ln);
        }
        if ($endDate instanceof DateTime) {
            $this->pdf->Cell($thirdColumnWidth, $lineHeight, $endDate->format('j M Y'), 0, 1);
        }

        // Consortium
        $this->parseCriterionLabel($this->translator->translate('txt-consortium'));
        $countries = implode(
            ', ',
            array_map(
                static function (Rationale $rationale) {
                    return $rationale->getCountry()->getCountry();
                },
                $project->getRationale()->toArray()
            )
        );
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
            static function (Challenge $challenge) {
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

        // Summary
        $this->parseCriterionLabel($this->translator->translate('txt-summary'));
        $this->pdf->MultiCell(
            self::$colWidths[2] + $thirdColumnWidth,
            $lineHeight,
            $project->getSummary(),
            0,
            1
        );


        $this->parseHeading($this->translator->translate('txt-participating-companies-and-countries'), 15, 'L', 'sub');

        $affiliationOverview = $this->affiliationService->findAffiliationByProjectAndWhich($project);
        $latestVersion       = $this->projectService->getLatestProjectVersion($project);

        $affCountries = [];
        foreach ($affiliationOverview as $affiliation) {
            $affCountries[] = [
                $affiliation->parseBranchedName(),
                $affiliation->getOrganisation()->getType()->getType(),
                $affiliation->getOrganisation()->getCountry()->getIso3(),
                null === $latestVersion
                    ? ''
                    : number_format(
                        $this->versionService
                        ->findTotalEffortVersionByAffiliationAndVersion(
                            $affiliation,
                            $latestVersion
                        ),
                        2
                    ),
            ];
        }

        $affCountries[] = [
            $this->translator->translate('txt-total'),
            '',
            '',
            null === $latestVersion
                ? ''
                : number_format(
                    $this->versionService
                    ->findTotalEffortVersionByProjectVersion(
                        $latestVersion
                    ),
                    2
                )];


        $this->parseCountryOverviewTable($affCountries, [239, 239, 239]);


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

    private function parseCountryOverviewTable(array $data, array $fillColor): void
    {
        // Colors, line width and bold font
        $this->pdf->SetFillColor(...$fillColor);
        $this->pdf->SetDrawColor(10, 10, 10);

        // Header
        $w = [90, 25, 25, 25];

        $header = [
            $this->translator->translate('txt-partner'),
            $this->translator->translate('txt-type'),
            $this->translator->translate('txt-country'),
            $this->translator->translate('txt-total-effort'),
        ];

        $num_headers = count($header);
        for ($i = 0; $i < $num_headers; ++$i) {
            $this->pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'L', 1);
        }
        $this->pdf->Ln();
        $fill = 0;
        foreach ($data as $row) {
            $this->pdf->Cell($w[0], 6, $row[0], 'LR', 0, 'L', $fill);
            $this->pdf->Cell($w[1], 6, $row[1], 'LR', 0, 'L', $fill);
            $this->pdf->Cell($w[2], 6, $row[2], 'LR', 0, 'L', $fill);
            $this->pdf->Cell($w[3], 6, $row[3], 'LR', 0, 'L', $fill);
            $this->pdf->Ln();
            $fill = !$fill;
        }
        $this->pdf->Cell(array_sum($w), 0, '', 'T');
    }

    private function parseSteeringGroupData(): void
    {
        // Stg decision
        $this->parseCriterionLabel($this->translator->translate('txt-steering-group-decision'));
        $finalScores = EvaluationReport::getVersionScores() + EvaluationReport::getReportScores();

        $finalScore = '';
        $fillColor = [239, 239, 239]; // Light grey
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
                $selectValues = Json::decode($result->getCriterionVersion()->getCriterion()->getValues(), true);
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

        $content = $hasScore ? (string)$result->getComment() : (string)$result->getValue();
        $lineHeight = $big ? self::$lineHeights['bigLine'] : self::$lineHeights['line'];
        $width = $big ? (self::$colWidths[2] + self::$colWidths[3]) : self::$colWidths[2];

        $calcLineHeight = $this->pdf->getStringHeight($width, $content);
        if ($calcLineHeight > $lineHeight) {
            $lineHeight = (int)ceil($calcLineHeight);
        }

        $this->checkPageEnding($lineHeight);
        $this->parseCriterionLabel($result->getCriterionVersion()->getCriterion()->getCriterion(), $lineHeight);
        // Scores only get added internal reports
        if ($hasScore && !$this->forDistribution) {
            $this->parseContentField($content, $fillColor, $lineHeight, $result->getScore());
        } else {
            $this->parseContentField($content, $fillColor, $lineHeight);
        }
    }

    private function parseFunderFeedbackOverview(): void
    {
        // Project name + report/version
        $project     = $this->evaluationReportService->getProject($this->evaluationReport);
        $countries   = $this->countryService->findCountryByProject($project);
        // Check to see if we have an active version
        $versionType = $this->evaluationReport->getProjectVersionReport()->getVersion()->getVersionType();

        $evaluationTypeId = ($versionType->getId() === Type::TYPE_FPP)
            ? EvaluationType::TYPE_FPP_EVALUATION
            : EvaluationType::TYPE_PO_EVALUATION;
        /** @var EvaluationType $evaluationType */
        $evaluationType = $this->projectService->find(
            EvaluationType::class,
            $evaluationTypeId
        );

        /** @var CreateEvaluation $evaluationPlugin */
        $evaluationPlugin = $this->controllerPluginManager->get(CreateEvaluation::class);

        $evaluationResult = $evaluationPlugin(
            [$project],
            $evaluationType,
            Evaluation::DISPLAY_EFFORT,
            MatrixFilter::SOURCE_VERSION
        );

        $projectEvaluationOverview = $this->renderer->render(
            'project/pdf/evaluation-project-overview',
            [
                'countries'        => $countries,
                'project'          => $project,
                'evaluationType'   => $evaluationType,
                'versionType'      => $versionType,
                'evaluationTypes'  => $this->evaluationService->findAll(EvaluationType::class),
                'evaluationResult' => $evaluationResult,
                'fundingStatuses'  => $this->evaluationService->getFundingStatusList(
                    $this->evaluationService->parseMainEvaluationType($evaluationType)
                ),
                'isEvaluation'     => $this->evaluationService->isEvaluation($evaluationType),
                'omitHeader'       => true,
            ]
        );

        $this->pdf->writeHTML($projectEvaluationOverview);
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
