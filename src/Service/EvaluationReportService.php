<?php
/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @category    Project
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2018 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\Service;

use Contact\Entity\Contact;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Evaluation\Entity\Report;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Entity\Report\Criterion\Type as CriterionType;
use Evaluation\Entity\Report\Criterion\Version as CriterionVersion;
use Evaluation\Entity\Report\ProjectReport as ProjectReportReport;
use Evaluation\Entity\Report\ProjectVersion as ProjectVersionReport;
use Evaluation\Entity\Report\Result as EvaluationReportResult;
use Evaluation\Entity\Report\Type as EvaluationReportType;
use Evaluation\Entity\Report\Version as EvaluationReportVersion;
use Project\Entity\ChangeRequest\Process;
use Project\Entity\Project;
use Project\Entity\Report\Report as ProjectReport;
use Project\Entity\Report\Reviewer as ReportReviewer;
use Project\Entity\Version\Reviewer as VersionReviewer;
use Project\Entity\Version\Type as VersionType;
use Project\Entity\Version\Version;
use function array_keys;
use function reset;
use function sprintf;

/**
 * Class EvaluationReportService
 *
 * @package Evaluation\Service
 */
class EvaluationReportService extends AbstractService
{
    public const STATUS_NEW         = 1;
    public const STATUS_IN_PROGRESS = 2;
    public const STATUS_FINAL       = 3;

    public function findReviewReportsByContact(Contact $contact, int $status = self::STATUS_NEW): array
    {
        return $this->entityManager->getRepository(EvaluationReport::class)
            ->findReviewReportsByContact($contact, $status);
    }

    public function getReviewers(EvaluationReport $evaluationReport): Collection
    {
        $projectVersionReport = $evaluationReport->getProjectVersionReport();
        $projectReportReport = $evaluationReport->getProjectReportReport();

        if ($projectVersionReport instanceof ProjectVersionReport) {
            if ($projectVersionReport->getReviewer() instanceof VersionReviewer) {
                return $projectVersionReport->getReviewer()->getVersion()->getReviewers();
            }

            if ($projectVersionReport->getProjectVersion() instanceof Version) {
                return $projectVersionReport->getProjectVersion()->getReviewers();
            }
        } elseif ($projectReportReport instanceof ProjectReportReport) {
            if ($projectReportReport->getReviewer() instanceof ReportReviewer) {
                return $projectReportReport->getReviewer()->getProjectReport()->getReviewers();
            }

            if ($projectReportReport->getReport() instanceof ProjectReport) {
                return $projectReportReport->getReport()->getReviewers();
            }
        }

        return new ArrayCollection();
    }

    public static function parseLabel(EvaluationReport $evaluationReport, string $template = '%s - %s - %s'): string
    {
        $project              = self::getProject($evaluationReport);
        $subject              = '';
        $projectVersionReport = $evaluationReport->getProjectVersionReport();
        $projectReportReport  = $evaluationReport->getProjectReportReport();

        if ($projectVersionReport instanceof ProjectVersionReport) {
            if ($projectVersionReport->getReviewer() instanceof VersionReviewer) {
                $subject = $projectVersionReport->getReviewer()->getVersion()->getVersionType()->getDescription();
            } elseif ($projectVersionReport->getProjectVersion() instanceof Version) {
                $subject = $projectVersionReport->getProjectVersion()->getVersionType()->getDescription();
            }
        } elseif ($projectReportReport instanceof ProjectReportReport) {
            if ($projectReportReport->getReviewer() instanceof ReportReviewer) {
                $subject = $projectReportReport->getReviewer()->getProjectReport()->parseName();
            } elseif ($projectReportReport->getReport() instanceof ProjectReport) {
                $subject = $projectReportReport->getReport()->parseName();
            }
        } else {
            return '';
        }

        return sprintf($template, $project->getCall(), $project->parseFullName(), $subject);
    }

    public static function getProject(EvaluationReport $evaluationReport): Project
    {
        $projectVersionReport = $evaluationReport->getProjectVersionReport();
        $projectReportReport = $evaluationReport->getProjectReportReport();

        if ($projectVersionReport instanceof ProjectVersionReport) {
            if ($projectVersionReport->getReviewer() instanceof VersionReviewer) {
                return $projectVersionReport->getReviewer()->getVersion()->getProject();
            }

            if ($projectVersionReport->getProjectVersion() instanceof Version) {
                return $projectVersionReport->getProjectVersion()->getProject();
            }
        } elseif ($projectReportReport instanceof ProjectReportReport) {
            if ($projectReportReport->getReviewer() instanceof ReportReviewer) {
                return $projectReportReport->getReviewer()->getProjectReport()->getProject();
            }

            if ($projectReportReport->getReport() instanceof ProjectReport) {
                return $projectReportReport->getReport()->getProject();
            }
        }

        return new Project();
    }

    public function parseCompletedPercentage(?EvaluationReport $evaluationReport = null): float
    {
        // Use a memory cache because this will be called multiple times for the same report version in lists
        static $requiredCounts = [];

        if ($evaluationReport === null) {
            return 0.0;
        }

        $resultCount = 0;
        /** @var EvaluationReport\Result $result */
        foreach ($evaluationReport->getResults() as $result) {
            if ($result->getCriterionVersion()->getRequired()
                && ((($result->getScore() !== null) && ($result->getScore() !== -1)) || !empty($result->getValue()))
            ) {
                $resultCount++;
            }
        }

        // No results is always 0%
        if ($resultCount === 0) {
            return 0.0;
        }

        $key = $evaluationReport->getVersion()->getId();
        if (!isset($requiredCounts[$key])) {
            $requiredCounts[$key] = $this->entityManager->getRepository(CriterionVersion::class)->count([
                'reportVersion' => $evaluationReport->getVersion(),
                'required'      => true
            ]);
        }

        // There are results, but no required criteria, so 100%
        if ($requiredCounts[$key] === 0) {
            return 100.0;
        }

        // Legacy evaluation reports can end up above 100%, so just cap it at 100
        $percentage = ($resultCount / $requiredCounts[$key]) * 100;
        return ($percentage > 100) ? 100.0 : $percentage;
    }

    public function getSortedResults(EvaluationReport $evaluationReport): array
    {
        // New evaluation reports already have their results sorted
        if ($evaluationReport->isEmpty()) {
            return $evaluationReport->getResults()->toArray();
        }

        return $this->entityManager->getRepository(EvaluationReport::class)
            ->getSortedResults($evaluationReport);
    }

    public function parseEvaluationReportType(EvaluationReport $evaluationReport): ?int
    {
        $projectVersionReport = $evaluationReport->getProjectVersionReport();
        $projectReportReport  = $evaluationReport->getProjectReportReport();

        if ($projectReportReport instanceof ProjectReportReport) {
            return EvaluationReportType::TYPE_REPORT;
        }

        if ($projectVersionReport instanceof ProjectVersionReport) {
            $versionType = null;
            $version     = null;
            if ($projectVersionReport->getReviewer() instanceof VersionReviewer) {
                $version     = $projectVersionReport->getReviewer()->getVersion();
                $versionType = $version->getVersionType();
            } elseif ($projectVersionReport->getVersion() instanceof Version) {
                $version     = $projectVersionReport->getVersion();
                $versionType = $version->getVersionType();
            }

            // Check whether it's a minor or major change reguest
            if ($versionType->getId() === VersionType::TYPE_CR) {
                // Old projects don't have a change request process, so default to major
                if ($version->getChangerequestProcess() === null) {
                    return EvaluationReportType::TYPE_MAJOR_CR_VERSION;
                }
                return ($version->getChangerequestProcess()->getType() === Process::TYPE_MINOR)
                    ? EvaluationReportType::TYPE_MINOR_CR_VERSION
                    : EvaluationReportType::TYPE_MAJOR_CR_VERSION;
            }

            /** @var EvaluationReportType $evaluationReportType */
            $evaluationReportType = $this->entityManager->getRepository(EvaluationReportType::class)
                ->findOneBy(['versionType' => $versionType]);

            if ($evaluationReportType instanceof EvaluationReportType) {
                return $evaluationReportType->getId();
            }
        }

        return null;
    }

    /*
     * Prepare a full evaluation report Doctrine entity structure based on the review id (Project\Entity\Report\Review
     * or Project\Entity\Version\Reviewer) for use in the Excel download or form for a new evaluation report.
     */
    public function prepareEvaluationReport(
        EvaluationReportVersion $evaluationReportVersion,
        int                     $reviewerId
    ): EvaluationReport {
        $evaluationReport = new EvaluationReport();
        $evaluationReport->setVersion($evaluationReportVersion);
        switch ($evaluationReportVersion->getReportType()->getId()) {
            case EvaluationReportType::TYPE_REPORT:
                /** @var ReportReviewer $reportReviewer */
                $reportReviewer = $this->entityManager->getRepository(ReportReviewer::class)->find($reviewerId);
                $projectReportReport = new EvaluationReport\ProjectReport();
                $projectReportReport->setReviewer($reportReviewer);
                $projectReportReport->setEvaluationReport($evaluationReport);
                $evaluationReport->setProjectReportReport($projectReportReport);
                break;
            case EvaluationReportType::TYPE_PO_VERSION:
            case EvaluationReportType::TYPE_FPP_VERSION:
            case EvaluationReportType::TYPE_MINOR_CR_VERSION:
            case EvaluationReportType::TYPE_MAJOR_CR_VERSION:
                /** @var VersionReviewer $versionReviewer */
                $versionReviewer = $this->entityManager->getRepository(VersionReviewer::class)->find($reviewerId);
                $projectVersionReport = new EvaluationReport\ProjectVersion();
                $projectVersionReport->setReviewer($versionReviewer);
                $projectVersionReport->setEvaluationReport($evaluationReport);
                $evaluationReport->setProjectVersionReport($projectVersionReport);
                break;
            default:
                return $evaluationReport;
        }

        // Prepare the empty results for the criteria
        $scoreValues            = array_keys(EvaluationReportResult::getScoreValues());
        $defaultScore           = reset($scoreValues);
        $sortedCriteriaVersions = $this->entityManager->getRepository(EvaluationReport::class)
            ->getSortedCriterionVersions($evaluationReportVersion);
        /** @var CriterionVersion $criterionVersion */
        foreach ($sortedCriteriaVersions as $criterionVersion) {
            $result = new EvaluationReportResult();
            if ($criterionVersion->getCriterion()->getHasScore()) {
                $result->setScore($defaultScore);
            }
            $result->setCriterionVersion($criterionVersion);
            $result->setEvaluationReport($evaluationReport);
            $evaluationReport->getResults()->add($result);
        }

        return $evaluationReport;
    }

    /*
     * Pre-fill the given FPP evaluation report with the PO evaluation report data for the linked project
     *
     * Note: This function will only work for individual reports and in order to also accommodate final reports it
     * needs to be updated.
     */
    public function preFillFppReport(EvaluationReport $fppEvaluationReport): void
    {
        // Only allow individual FPP based evaluation reports
        if (($fppEvaluationReport->getVersion()->getReportType()->getId() === EvaluationReportType::TYPE_FPP_VERSION)
            && ($fppEvaluationReport->getProjectVersionReport()->getVersion() === null)
        ) {
            $now       = new DateTime();
            $project   = self::getProject($fppEvaluationReport);
            $poVersion = null;
            /** @var Version $version */
            foreach ($project->getVersion() as $version) {
                if ($version->getVersionType()->getId() === VersionType::TYPE_PO) {
                    $poVersion = $version;
                    break;
                }
            }
            if ($poVersion instanceof Version) {
                // Get the PO final evaluation report
                $poVersionEvaluationReport = $poVersion->getProjectVersionReport();
                if ($poVersionEvaluationReport instanceof ProjectVersionReport) {
                    /** @var Report $poEvaluationReport */
                    $poEvaluationReport = $poVersionEvaluationReport->getEvaluationReport();
                    $fppEvaluationReport->setScore($poEvaluationReport->getScore());
                    foreach ($poEvaluationReport->getResults() as $poResult) {
                        foreach ($fppEvaluationReport->getResults() as &$fppResult) {
                            // Matching criterion? Transfer the result.
                            // We assume the new $fppEvaluationReport has been pre-filled with empty results.
                            if ($poResult->getCriterionVersion()->getCriterion()->getId()
                                === $fppResult->getCriterionVersion()->getCriterion()->getId()
                            ) {
                                $fppResult->setDateCreated($now);
                                $fppResult->setDateUpdated($now);
                                $fppResult->setScore($poResult->getScore());
                                $fppResult->setValue($poResult->getValue());
                                $fppResult->setComment($poResult->getComment());
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    public function copyEvaluationReportVersion(EvaluationReportVersion $sourceReportVersion): EvaluationReportVersion
    {
        $targetReportVersion = new EvaluationReportVersion();
        $targetReportVersion->setReportType($sourceReportVersion->getReportType());
        $targetReportVersion->setLabel($sourceReportVersion->getLabel());
        $targetReportVersion->setDescription($sourceReportVersion->getDescription());
        $targetReportVersion->setTopics(new ArrayCollection($sourceReportVersion->getTopics()->toArray()));
        /** @var CriterionVersion $criterionVersion */
        foreach ($sourceReportVersion->getCriterionVersions() as $criterionVersion) {
            // New instance as the results shouldn't be transferred
            $newCriterionVersion = new CriterionVersion();
            $newCriterionVersion->setCriterion($criterionVersion->getCriterion());
            $newCriterionVersion->setReportVersion($targetReportVersion);
            $newCriterionVersion->setType($criterionVersion->getType());
            $newCriterionVersion->setSequence($criterionVersion->getSequence());
            $newCriterionVersion->setRequired($criterionVersion->getRequired());
            $newCriterionVersion->setConfidential($criterionVersion->getConfidential());
            $newCriterionVersion->setHighlighted($criterionVersion->getHighlighted());
            $newCriterionVersion->setVersionTopics($criterionVersion->getVersionTopics());
            $targetReportVersion->getCriterionVersions()->add($newCriterionVersion);
        }

        return $targetReportVersion;
    }

    public function findReportVersionForProjectVersion(Version $projectVersion): ?EvaluationReportVersion
    {
        return $this->entityManager->getRepository(EvaluationReportVersion::class)
            ->findByProjectVersion($projectVersion);
    }

    public function findReportVersionForProjectReport(): ?EvaluationReportVersion
    {
        return $this->entityManager->getRepository(EvaluationReportVersion::class)->findForProjectReport();
    }

    public function typeIsConfidential(CriterionType $type, EvaluationReportVersion $reportVersion): bool
    {
        $count = $this->entityManager->getRepository(EvaluationReport\Criterion\Version::class)->count([
            'type'          => $type,
            'reportVersion' => $reportVersion,
            'confidential'  => false
        ]);
        return ($count === 0);
    }

    public function typeIsDeletable(CriterionType $type): bool
    {
        $count = $this->entityManager->getRepository(EvaluationReport\Criterion\Version::class)->count([
            'type' => $type,
        ]);
        return ($count === 0);
    }

//    public function migrate(): array
//    {
//        $log = [];
//        /** @var PDOConnection $pdo */
//        $pdo = $this->entityManager->getConnection()->getWrappedConnection();
//
//        $log[] = 'Begin transaction';
//        $pdo->beginTransaction();
//
//        // Truncate
//        $log[] = 'Truncate tables';
//        $tables = [
//            'evaluation_report2',
//            'evaluation_report2_criterion',
//            'evaluation_report2_criterion_category',
//            'evaluation_report2_criterion_report_type',
//            'evaluation_report2_criterion_topic',
//            'evaluation_report2_criterion_topic_version',
//            'evaluation_report2_criterion_type',
//            'evaluation_report2_criterion_version',
//            'evaluation_report2_criterion_version_topic',
//            'evaluation_report2_project_report',
//            'evaluation_report2_project_version',
//            'evaluation_report2_result',
//            'evaluation_report2_type',
//            'evaluation_report2_version',
//            'evaluation_report2_window',
//            'evaluation_report2_window_report_version'
//        ];
//
//        $statement = $pdo->prepare('SET FOREIGN_KEY_CHECKS = 0');
//        $statement->execute();
//        foreach ($tables as $table) {
//            $sql = sprintf('TRUNCATE TABLE %s', $table);
//            $log[] = $sql;
//            $pdo->exec($sql);
//        }
//        $statement = $pdo->prepare('SET FOREIGN_KEY_CHECKS = 1');
//        $statement->execute();
//
//        // 1/16
//        $log[] = 'Criterion categories: evaluation_report2_criterion_category';
//        $sql = 'INSERT INTO evaluation_report2_criterion_category (category_id, `sequence`, category)
//            SELECT category_id, `sequence`, category FROM evaluation_report_criterion_category';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 2/16
//        $log[] = 'Criterion types: evaluation_report2_criterion_type';
//        $sql = 'INSERT INTO evaluation_report2_criterion_type (type_id, category_id, `sequence`, `type`)
//            SELECT type_id, category_id, `sequence`, `type` FROM evaluation_report_criterion_type';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 3/16
//        $log[] = 'Report windows: evaluation_report2_window';
//        $sql = 'INSERT INTO evaluation_report2_window SELECT * FROM evaluation_report_window';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 4/16
//        $log[] = 'Report types: evaluation_report2_type';
//        $sql = 'INSERT INTO `evaluation_report2_type` VALUES
//        (1, NULL, 0, \'Progress report\'),
//        (2, 1, 1, \'Project outline\'),
//        (3, 2, 2, \'Full project proposal\'),
//        (4, 3, 3, \'Minor change request\'),
//        (5, 3, 4, \'Major change request\')';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 5/16
//        $log[] = 'Report versions: evaluation_report2_version';
//        $now = (new \DateTime())->format('Y-m-d H:i:s');
//        $sql = 'INSERT INTO `evaluation_report2_version` VALUES
//        (1, 1, \'PPR evaluation report 1.0\', \'First version of the PPR evaluation\', 0, \''.$now.'\'),
//        (2, 2, \'PO evaluation report 1.0\', \'First version of the PO evaluation\', 0, \''.$now.'\'),
//        (3, 3, \'FPP evaluation report 1.0\', \'First version of the FPP evaluation\', 0, \''.$now.'\'),
//        (4, 4, \'Minor CR evaluation report 1.0\', \'First version of the minor CR evaluation\', 0, \''.$now.'\'),
//        (5, 5, \'Major CR evaluation report 1.0\', \'First version of the major CR evaluation\', 0, \''.$now.'\')';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 6/16
//        $log[] = 'Reports: evaluation_report2';
//        $sql = 'INSERT INTO evaluation_report2
//            SELECT er.evaluation_report_id, rv.version_id, er.final, er.score, er.date_created, er.date_updated FROM evaluation_report er
//            INNER JOIN evaluation_report2_version rv ON rv.type_id = er.type_id
//            WHERE rv.archived = 0
//            ORDER BY er.evaluation_report_id';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 7/16
//        $log[] = 'Report project versions: evaluation_report2_project_version';
//        $sql = 'INSERT INTO evaluation_report2_project_version
//            SELECT project_version_id, evaluation_report_id, project_version_review_id, version_id, date_created, date_updated
//            FROM evaluation_report_project_version';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 8/16
//        $log[] = 'Report project reports: evaluation_report2_project_report';
//        $sql = 'INSERT INTO evaluation_report2_project_report
//            SELECT project_report_id, evaluation_report_id, report_review_id, report_id, project_status, date_created, date_updated
//            FROM evaluation_report_project_report';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 9/16
//        $log[] = 'Report project criteria: evaluation_report2_criterion';
//        $sql = 'INSERT INTO evaluation_report2_criterion
//            SELECT criterion_id, `sequence`, criterion, help_block, input_type, `values`, has_score, 0
//            FROM evaluation_report_criterion';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 10/16
//        $log[] = 'Report project criteria versions: evaluation_report2_criterion_version';
//        $sql = 'INSERT INTO evaluation_report2_criterion_version (criterion_id, version_id, type_id, `sequence`, required, confidential, highlighted)
//            SELECT cr.criterion_id, rv.version_id, cr.type_id, cr.`sequence`, cr.is_required, cr.is_confidential, cr.is_highlighted
//            FROM evaluation_report_criterion cr
//            INNER JOIN evaluation_report_criterion_report_type crt ON crt.criterion_id = cr.criterion_id
//            INNER JOIN evaluation_report2_version rv ON rv.type_id = crt.type_id
//            WHERE rv.archived = 0
//            ORDER BY cr.criterion_id';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 11/16
//        $log[] = 'Report project criteria topics: evaluation_report2_criterion_topic';
//        $sql = 'INSERT INTO evaluation_report2_criterion_topic
//            SELECT * FROM evaluation_report_criterion_topic';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 12/16
//        $log[] = 'Report project criteria topic versions: evaluation_report2_criterion_topic_version';
//        $sql = 'INSERT INTO evaluation_report2_criterion_topic_version
//            SELECT rv.version_id, trt.topic_id FROM evaluation_report_criterion_topic_report_type trt
//            INNER JOIN evaluation_report2_version rv ON rv.type_id = trt.type_id
//            WHERE rv.archived = 0';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 13/16
//        $log[] = 'Criteria for report type: evaluation_report2_criterion_report_type';
//        $sql = 'INSERT INTO evaluation_report2_criterion_report_type
//            SELECT * FROM evaluation_report_criterion_report_type';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 14/16
//        $log[] = 'Report project criteria version topics: evaluation_report2_criterion_version_topic';
//        $sql = 'INSERT INTO evaluation_report2_criterion_version_topic (criterion_version_id, topic_id, weight)
//            SELECT cv.criterion_version_id, ct.topic_id, ct.weight FROM evaluation_report_criterion_criterion_topic ct
//            INNER JOIN evaluation_report2_criterion_version cv ON cv.criterion_id = ct.criterion_id';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 15/16
//        $log[] = 'Report results: evaluation_report2_result';
//        $sql = 'INSERT INTO evaluation_report2_result
//                (evaluation_report_id, criterion_version_id, date_created, date_updated, date_changed, score, `value`, `comment`)
//            SELECT r.evaluation_report_id, cv.criterion_version_id, r.date_created, r.date_updated, r.date_changed, r.score, r.`value`, r.`comment`
//            FROM evaluation_report_result r
//            INNER JOIN evaluation_report er ON er.evaluation_report_id = r.evaluation_report_id
//            INNER JOIN evaluation_report2_version rv ON rv.type_id = er.type_id
//            INNER JOIN evaluation_report2_criterion_version cv ON cv.criterion_id = r.criterion_id
//            WHERE cv.version_id = rv.version_id
//            AND rv.archived = 0';
//        $statement = $pdo->prepare($sql);
//        $log[] = $statement->execute() ? 'OK' : 'ERROR';
//
//        // 16/16
//        $log[] = 'Skipping window report versions: evaluation_report2_window_report_version';
//
//        $log[] = $pdo->commit() ? 'Commit OK' : 'Commit failed: ' . implode(', ', $pdo->errorInfo()) ;
//
//        return $log;
//    }
}
