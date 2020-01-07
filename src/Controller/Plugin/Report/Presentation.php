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

use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Entity\Report\Result;
use Evaluation\Options\ModuleOptions;
use Evaluation\Service\EvaluationReportService;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Shape\Table;
use PhpOffice\PhpPresentation\Shape\Table\Cell;
use PhpOffice\PhpPresentation\Slide;
use PhpOffice\PhpPresentation\Slide\Background\Image;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Border;
use PhpOffice\PhpPresentation\Style\Bullet;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Fill;
use Project\Entity\Rationale;
use Laminas\Http\Headers;
use Laminas\Http\Response;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use function array_map;
use function ceil;
use function count;
use function explode;
use function implode;
use function ob_end_flush;
use function ob_get_clean;
use function ob_get_length;
use function ob_start;
use function sort;
use function sprintf;
use function str_replace;
use function strlen;

/**
 * Class Presentation
 *
 * @package Evaluation\Controller\Plugin\Report
 */
final class Presentation extends AbstractPlugin
{
    private const FONT = 'Arial';

    private static $scoreColors
        = [
            EvaluationReport::SCORE_LOW          => 'FFFF0000',
            EvaluationReport::SCORE_MIDDLE_MINUS => 'FFC6EfCE',
            EvaluationReport::SCORE_MIDDLE       => 'FFC6EfCE',
            EvaluationReport::SCORE_MIDDLE_PLUS  => 'FFC6EfCE',
            EvaluationReport::SCORE_TOP          => 'FF00A651',
            EvaluationReport::SCORE_APPROVED     => 'FF00A651',
            EvaluationReport::SCORE_REJECTED     => 'FFFF0000'
        ];
    /**
     * @var ModuleOptions
     */
    private $moduleOptions;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var PhpPresentation
     */
    private $presentation;
    /**
     * @var string
     */
    private $fileName;

    public function __construct(
        ModuleOptions $moduleOptions,
        TranslatorInterface $translator
    ) {
        $this->moduleOptions = $moduleOptions;
        $this->translator = $translator;
    }

    public function __invoke(array $evaluationReports): Presentation
    {
        $presentation = new PhpPresentation();
        $presentation->getDocumentProperties()->setCreator($this->moduleOptions->getReportAuthor());

        // Add title slide
        $titleBackground = new Image();
        $titleBackground->setPath($this->moduleOptions->getPresentationTemplates()['title']);
        $titleSlide = $presentation->getActiveSlide();
        $titleSlide->setBackground($titleBackground);
        $titleShape = $titleSlide->createRichTextShape();
        $titleShape->setWidthAndHeight(900, 60)->setOffsetX(30)->setOffsetY(100);

        // Title
        $whiteColor = new Color(Color::COLOR_WHITE);
        $titleRun = $titleShape->createTextRun($this->translator->translate('txt-title-here'));
        $titleRun->getFont()->setBold(true)->setSize(34)->setColor($whiteColor)
            ->setName(self::FONT);

        // Sub title
        $subTitleShape = $titleSlide->createRichTextShape();
        $subTitleShape->setWidthAndHeight(900, 150)->setOffsetX(30)->setOffsetY(170);
        $run = $subTitleShape->createTextRun($this->translator->translate('txt-sub-title-here'));
        $run->getFont()->setSize(20)->setColor($whiteColor)->setName(self::FONT);

        $this->presentation = $presentation;
        $this->fileName = 'presentation.pptx';

        /** @var EvaluationReport $evaluationReport */
        foreach ($evaluationReports as $evaluationReport) {
            $this->parseProjectSlide($evaluationReport);
        }

        return $this;
    }

    private function parseProjectSlide(EvaluationReport $evaluationReport): void
    {
        $background = new Image();
        $background->setPath($this->moduleOptions->getPresentationTemplates()['background']);
        $slide = $this->presentation->createSlide();
        $slide->setBackground($background);

        if (isset(self::$scoreColors[$evaluationReport->getScore()])) {
            $scoreShape = $slide->createRichTextShape();
            $scoreShape->setWidthAndHeight(45, 90)->setOffsetX(0)->setOffsetY(15);
            $scoreColor = new Color(self::$scoreColors[$evaluationReport->getScore()]);
            $scoreShape->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor($scoreColor);
        }

        $project = EvaluationReportService::getProject($evaluationReport);

        // Title
        $challenge = $project->getProjectChallenge()->first()->getChallenge()->getChallenge();
        $projectLabel = sprintf('%s - %s (%s)', $project->getProject(), $project->getNumber(), $challenge);
        $headerShape = $slide->createRichTextShape();
        $headerShape->setWidthAndHeight(885, 70)->setOffsetX(50)->setOffsetY(30);
        $labelRun = $headerShape->createTextRun($projectLabel);
        $labelRun->getFont()->setBold(true)->setSize(24)->setColor(new Color('FF00A651'))
            ->setName(self::FONT);
        $headerShape->createBreak();
        $titleRun = $headerShape->createTextRun($project->getTitle());
        $titleLength = strlen($project->getTitle());
        // Crude way to make the full name fit
        $fontSize = 18;
        if ($titleLength > 65) {
            $fontSize = 18 - ceil(($titleLength - 65) / 7);
        }
        $titleRun->getFont()->setBold(true)->setSize($fontSize)->setColor(new Color('FF7F7F7F'))
            ->setName(self::FONT);

        // Body
        $bodyShape = $slide->createRichTextShape();
        $bodyShape->setWidthAndHeight(885, 590)->setOffsetX(50)->setOffsetY(115);
        // Summary
        $this->parseSectionHeader($bodyShape, $this->translator->translate('txt-summary'));
        $bodyShape->createBreak();
        $this->parsePlainTextRun(
            $bodyShape,
            (string)$this->findResult($evaluationReport, 'Summary')->getValue()
        );
        $bodyShape->createBreak();
        // Countries
        $this->parseSectionHeader($bodyShape, $this->translator->translate('txt-countries'));
        $bodyShape->createBreak();
        $countries = array_map(
            function (Rationale $rationale) {
                return $rationale->getCountry()->getCountry();
            },
            $project->getRationale()->toArray()
        );
        sort($countries);
        $this->parsePlainTextRun($bodyShape, implode(', ', $countries));
        $bodyShape->createBreak();
        // Comments
        $this->parseSectionHeader($bodyShape, $this->translator->translate('txt-evaluation-comments'));
        // Recommendations
        $bodyShape->createBreak();
        $this->parseSectionHeader($bodyShape, $this->translator->translate('txt-recommendations'));

        // Comments table
        $this->parseCommentsTable($slide, $evaluationReport);

        // Recommendations table
        $this->parseRecommendationsTable($slide, $evaluationReport);
    }

    private function parseSectionHeader(RichText $richTextShape, string $headerText): RichText\Run
    {
        $run = $richTextShape->createTextRun($headerText);
        $run->getFont()->setBold(true)->setSize(14)->setColor(new Color('FF00A651'))
            ->setName(self::FONT);
        return $run;
    }

    private function parsePlainTextRun(RichText $richTextShape, string $text): RichText\Run
    {
        $run = $richTextShape->createTextRun($text);
        $run->getFont()->setSize(12)->setName(self::FONT);
        return $run;
    }

    private function findResult(EvaluationReport $evaluationReport, string $criterion): Result
    {
        /** @var Result $result */
        foreach ($evaluationReport->getResults() as $result) {
            if ($result->getCriterionVersion()->getCriterion()->getCriterion() === $criterion) {
                return $result;
            }
        }
        return new Result();
    }

    private function parseCommentsTable(Slide $slide, EvaluationReport $evaluationReport): Table
    {
        $tableShapeComments = $slide->createTableShape(2);
        $tableShapeComments->setWidth(885);
        $tableShapeComments->setOffsetX(50);
        $tableShapeComments->setOffsetY(300);
        $tableShapeComments->getBorder()->setLineStyle(Border::LINE_NONE);

        $titleRow = $tableShapeComments->createRow();
        $titleRow->setHeight(32);

        $cellTitle1 = $titleRow->getCell(0);
        $this->parseTableTitleCell($cellTitle1, $this->translator->translate('txt-plus'));
        $cellTitle2 = $titleRow->getCell(1);
        $this->parseTableTitleCell($cellTitle2, $this->translator->translate('txt-minus'));

        $contentRow = $tableShapeComments->createRow();
        $contentRow->setHeight(100);
        $cellContent1 = $contentRow->getCell(0);
        $this->parseTableContentCell($cellContent1, $this->findResult($evaluationReport, 'Plus')->getValue());
        $cellContent2 = $contentRow->getCell(1);
        $this->parseTableContentCell($cellContent2, $this->findResult($evaluationReport, 'Minus')->getValue());

        return $tableShapeComments;
    }

    private function parseTableTitleCell(Cell $cell, ?string $text): Cell
    {
        $white = new Color(Color::COLOR_WHITE);
        $cell->getBorders()->getTop()->setLineStyle(Border::LINE_NONE);
        $cell->getBorders()->getRight()->setColor($white);
        $cell->getBorders()->getBottom()->setLineWidth(3)->setColor($white);
        $cell->getBorders()->getLeft()->setColor($white);
        $cell->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF00A651'));
        $cell->getActiveParagraph()->getAlignment()->setMarginLeft(4);
        $cell->getActiveParagraph()->getAlignment()->setMarginTop(4);
        $run = $cell->createTextRun($text);
        $run->getFont()->setBold(true)->setSize(14)->setColor($white)->setName(self::FONT);
        return $cell;
    }

    private function parseTableContentCell(Cell $cell, ?string $text): Cell
    {
        $white = new Color(Color::COLOR_WHITE);
        $cell->getBorders()->getTop()->setLineWidth(3)->setColor($white);
        $cell->getBorders()->getRight()->setColor($white);
        $cell->getBorders()->getBottom()->setLineStyle(Border::LINE_NONE);
        $cell->getBorders()->getLeft()->setColor($white);
        $cell->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFCBE1D0'));
        $cell->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setMarginLeft(10)
            ->setIndent(-10);
        $cell->getActiveParagraph()->getAlignment()->setMarginTop(4)->setMarginRight(4);
        $cell->getActiveParagraph()->getBulletStyle()->setBulletType(Bullet::TYPE_BULLET);
        $cell->getActiveParagraph()->getBulletStyle()->setBulletChar('▪');

        //$cell->getActiveParagraph()->getAlignment()->getLevel();

        $text = str_replace("\r", '', $text);
        $parts = explode("\n", $text);
        $total = count($parts);
        for ($i = 0; $i < $total; $i++) {
            $paragraph = ($i === 0) ? $cell->getActiveParagraph() : $cell->createParagraph();
            $run = $paragraph->createTextRun($parts[$i]);
            $run->getFont()->setSize(11)->setName(self::FONT);
        }
        return $cell;
    }

    private function parseRecommendationsTable(Slide $slide, EvaluationReport $evaluationReport): Table
    {
        $tableShapeRecommendations = $slide->createTableShape(2);
        $tableShapeRecommendations->setWidth(885);
        $tableShapeRecommendations->setOffsetX(50);
        $tableShapeRecommendations->setOffsetY(450);
        $tableShapeRecommendations->getBorder()->setLineStyle(Border::LINE_NONE);

        $titleRow = $tableShapeRecommendations->createRow();
        $titleRow->setHeight(32);

        $cellTitle1 = $titleRow->getCell(0);
        $this->parseTableTitleCell($cellTitle1, $this->translator->translate('txt-mandatory'));
        $cellTitle2 = $titleRow->getCell(1);
        $this->parseTableTitleCell($cellTitle2, $this->translator->translate('txt-recommended'));

        $contentRow = $tableShapeRecommendations->createRow();
        $contentRow->setHeight(100);
        $cellContent1 = $contentRow->getCell(0);
        $this->parseTableContentCell($cellContent1, $this->findResult($evaluationReport, 'Mandatory')->getValue());
        $cellContent2 = $contentRow->getCell(1);
        $this->parseTableContentCell($cellContent2, $this->findResult($evaluationReport, 'Recommended')->getValue());

        return $tableShapeRecommendations;
    }

    public function parseResponse(): Response
    {
        $response = new Response();
        if (! ($this->presentation instanceof PhpPresentation)) {
            return $response->setStatusCode(Response::STATUS_CODE_404);
        }

        $writer = IOFactory::createWriter($this->presentation, 'PowerPoint2007');

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
                'Content-Disposition' => 'attachment; filename="' . $this->fileName . '"',
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
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

    public function getPresentation(): PhpPresentation
    {
        return $this->presentation;
    }
}
