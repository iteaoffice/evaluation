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

use Doctrine\Common\Collections\Collection;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Entity\Report\Criterion;
use Evaluation\Entity\Report\Result as EvaluationReportResult;
use Evaluation\Service\EvaluationReportService;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Class ExcelImport
 * @package Evaluation\Controller\Plugin\Report
 */
final class ExcelImport extends AbstractPlugin
{
    /**
     * @var EvaluationReportService
     */
    private $evaluationReportService;

    /**
     * @var Spreadsheet
     */
    private $data = [];

    /**
     * @var int
     */
    private $finalScore;
    /**
     * @var int
     */
    private $projectStatus;

    /**
     * @var bool
     */
    private $hasParseErrors = false;

    public function __construct(EvaluationReportService $evaluationReportService)
    {
        $this->evaluationReportService = $evaluationReportService;
    }

    public function __invoke(string $file): ReportExcelImport
    {
        $this->data = [];
        try {
            $fileType            = IOFactory::identify($file);
            $reader              = IOFactory::createReader($fileType);
            $excel               = $reader->load($file);
            $sheet               = $excel->getSheet(2);
            $highestRow          = $sheet->getHighestRow();
            $this->data          = $sheet->rangeToArray('A1:E' . $highestRow, null, true, false);
            // Will have to use the deprecated getCalculatedValue() as getOldCalculatedValue() gives an empty result
            $finalScore          = $sheet->getCell('F1')->getCalculatedValue();
            $this->finalScore    = (empty($finalScore) ? null : (int)$finalScore);
            $projectStatus       = $sheet->getCell('G1')->getCalculatedValue();
            $this->projectStatus = (empty($projectStatus) ? null : (int)$projectStatus);
            unset($excel);
        } catch (Exception $exception) {
            $this->data = [];
            $this->hasParseErrors = true;
        }
        return $this;
    }

    /*
     * An excel is outdated when there are existing results, but the Excel doesn't include the result IDs
     * This should only be called when there are no parse errors of course!
     */
    public function excelIsOutdated(EvaluationReport $evaluationReport): bool
    {
        foreach ($this->data as $row) {
            if (!empty($row[1])) {
                // An Excel with result IDs is never outdated, so short-circuit
                return false;
            }
        }

        return ($evaluationReport->getResults()->count() > 0);
    }

    public function hasParseErrors(): bool
    {
        return $this->hasParseErrors;
    }

    public function import(EvaluationReport $evaluationReport): bool
    {
        if ($this->hasParseErrors) {
            return false;
        }
        $success = true;
        $isNew   = $evaluationReport->isEmpty();
        // New reports are pre-filled with dummy results. Clear them to prevent duplicates.
        if ($isNew) {
            $evaluationReport->getResults()->clear();
        }
        foreach ($this->data as $row) {
            $score   = (is_float($row[2]) ? (int)$row[2] : $row[2]);
            $value   = (empty(trim((string)$row[3])) ? null : trim((string)$row[3]));
            $comment = (empty(trim((string)$row[4])) ? null : trim((string)$row[4]));

            // New report or the criterion was added after the existing results were saved
            if ($isNew || empty($row[1])) {
                $result = new EvaluationReportResult();
                $result->setEvaluationReport($evaluationReport);
                /** @var Criterion\Version $criterionVersion */
                $criterionVersion = $this->evaluationReportService->find(Criterion\Version::class, (int)$row[0]);
                $result->setCriterionVersion($criterionVersion);
                $result->setScore($score);
                $result->setValue($value);
                $result->setComment($comment);
                $evaluationReport->getResults()->add($result);
            }
            // Update existing result
            else {
                /** @var EvaluationReportResult $result */
                $result = $this->findInCollection($evaluationReport->getResults(), (int)$row[1]);
                if ($result instanceof EvaluationReportResult) {
                    $result->setScore($score);
                    $result->setValue($value);
                    $result->setComment($comment);
                } else {
                    $success = false;
                }
            }
        }
        $evaluationReport->setScore($this->finalScore);
        if ($evaluationReport->getProjectReportReport() instanceof EvaluationReport\ProjectReport) {
            $evaluationReport->getProjectReportReport()->setProjectStatus($this->projectStatus);
        }

        return $success;
    }

    /**
     * Find an entity in a Doctrine collection
     * @param Collection $collection
     * @param int $id
     * @return object|null
     */
    private function findInCollection(Collection $collection, int $id)
    {
        foreach ($collection as $entity) {
            if ($entity->getId() === $id) {
                return $entity;
            }
        }
        return null;
    }
}
