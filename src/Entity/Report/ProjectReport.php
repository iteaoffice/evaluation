<?php
/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @category    Project
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2017 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\Entity\Report;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Evaluation\Entity\AbstractEntity;
use Evaluation\Entity\Report as EvaluationReport;
use Gedmo\Mapping\Annotation as Gedmo;
use Project\Entity\Report\Report;
use Project\Entity\Report\Reviewer;
use Zend\Form\Annotation;

/**
 * @ORM\Table(name="evaluation_report2_project_report")
 * @ORM\Entity
 */
class ProjectReport extends AbstractEntity
{
    public const PROJECT_STATUS_EXCELLENT = 1; // Excellent project status
    public const PROJECT_STATUS_AVERAGE = 2; // Average project status
    public const PROJECT_STATUS_ALARMING = 3; // Bad project status

    private static $projectStatuses
        = [
            self::PROJECT_STATUS_EXCELLENT => 'txt-excellent',
            self::PROJECT_STATUS_AVERAGE   => 'txt-average',
            self::PROJECT_STATUS_ALARMING  => 'txt-alarming'
        ];

    /**
     * @ORM\Column(name="project_report_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Annotation\Exclude()
     *
     * @var integer
     */
    private $id;
    /**
     * @ORM\OneToOne(targetEntity="Evaluation\Entity\Report", cascade={"persist","remove"}, inversedBy="projectReportReport")
     * @ORM\JoinColumn(name="evaluation_report_id", referencedColumnName="evaluation_report_id", nullable=false)
     * @Annotation\Exclude()
     *
     * @var EvaluationReport
     */
    private $evaluationReport;
    /**
     * @ORM\Column(name="project_status", type="smallint", length=5, options={"unsigned":true}, nullable=true)
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Options({
     *     "label":"txt-evaluation-report-project-status-label",
     *     "help-block":"txt-evaluation-report-project-status-help-block",
     *     "array":"projectStatuses"
     * })
     *
     * @var int
     */
    private $projectStatus;
    /**
     * Only set for final evaluations (so is nullable)
     *
     * @ORM\OneToOne(targetEntity="Project\Entity\Report\Report", cascade={"persist"}, inversedBy="projectReportReport")
     * @ORM\JoinColumn(name="report_id", referencedColumnName="report_id", nullable=true)
     * @Annotation\Exclude()
     *
     * @var Report
     */
    private $report;
    /**
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="create")
     * @Annotation\Exclude()
     *
     * @var DateTime
     */
    private $dateCreated;
    /**
     * @ORM\Column(name="date_updated", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     * @Annotation\Exclude()
     *
     * @var DateTime
     */
    private $dateUpdated;
    /**
     * Only set for individual review reports (so is nullable)
     *
     * @ORM\OneToOne(targetEntity="Project\Entity\Report\Reviewer", cascade={"persist"}, inversedBy="projectReportReport")
     * @ORM\JoinColumn(name="report_review_id", referencedColumnName="review_id", nullable=true)
     * @Annotation\Exclude()
     *
     * @var Reviewer
     */
    private $reviewer;

    public static function getProjectStatuses(): array
    {
        return self::$projectStatuses;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): ProjectReport
    {
        $this->id = $id;
        return $this;
    }

    public function getEvaluationReport(): ?EvaluationReport
    {
        return $this->evaluationReport;
    }

    public function setEvaluationReport(EvaluationReport $evaluationReport): ProjectReport
    {
        $this->evaluationReport = $evaluationReport;
        return $this;
    }

    public function getProjectStatus(): ?int
    {
        return $this->projectStatus;
    }

    public function setProjectStatus(?int $projectStatus): ProjectReport
    {
        $this->projectStatus = $projectStatus;
        return $this;
    }

    public function getDateCreated(): ?DateTime
    {
        return $this->dateCreated;
    }

    public function setDateCreated(DateTime $dateCreated): ProjectReport
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    public function getDateUpdated(): ?DateTime
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(DateTime $dateUpdated): ProjectReport
    {
        $this->dateUpdated = $dateUpdated;
        return $this;
    }

    public function getReviewer(): ?Reviewer
    {
        return $this->reviewer;
    }

    public function setReviewer(Reviewer $reviewer): ProjectReport
    {
        $this->reviewer = $reviewer;
        return $this;
    }

    public function getReport(): ?Report
    {
        return $this->report;
    }

    public function setReport(Report $report): ProjectReport
    {
        $this->report = $report;
        return $this;
    }
}
