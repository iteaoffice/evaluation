<?php

/**
*
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2019 ITEA Office (https://itea3.org)
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
use Project\Entity\Version\Reviewer;
use Project\Entity\Version\Version;
use Laminas\Form\Annotation;

/**
 * @ORM\Table(name="evaluation_report2_project_version")
 * @ORM\Entity
 */
class ProjectVersion extends AbstractEntity
{
    /**
     * @ORM\Column(name="project_version_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Annotation\Exclude()
     *
     * @var int
     */
    private $id;
    /**
     * @ORM\OneToOne(targetEntity="Evaluation\Entity\Report", cascade={"persist","remove"}, inversedBy="projectVersionReport")
     * @ORM\JoinColumn(name="evaluation_report_id", referencedColumnName="evaluation_report_id", nullable=false)
     * @Annotation\Exclude()
     *
     * @var EvaluationReport
     */
    private $evaluationReport;
    /**
     * Only set for individual review reports (so is nullable)
     *
     * @ORM\OneToOne(targetEntity="Project\Entity\Version\Reviewer", cascade={"persist"}, inversedBy="projectVersionReport")
     * @ORM\JoinColumn(name="project_version_review_id", referencedColumnName="review_id", nullable=true)
     * @Annotation\Exclude()
     *
     * @var Reviewer|null
     */
    private $reviewer;
    /**
     * Only set for final evaluations (so is nullable)
     *
     * @ORM\OneToOne(targetEntity="Project\Entity\Version\Version", cascade={"persist"}, inversedBy="projectVersionReport")
     * @ORM\JoinColumn(name="version_id", referencedColumnName="version_id", nullable=true)
     * @Annotation\Exclude()
     *
     * @var Version|null
     */
    private $version;
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): ProjectVersion
    {
        $this->id = $id;
        return $this;
    }

    public function getEvaluationReport(): ?EvaluationReport
    {
        return $this->evaluationReport;
    }

    public function setEvaluationReport(EvaluationReport $evaluationReport): ProjectVersion
    {
        $this->evaluationReport = $evaluationReport;
        return $this;
    }

    public function getReviewer(): ?Reviewer
    {
        return $this->reviewer;
    }

    public function setReviewer(Reviewer $reviewer): ProjectVersion
    {
        $this->reviewer = $reviewer;
        return $this;
    }

    public function getProjectVersion(): ?Version
    {
        return $this->version;
    }

    public function setProjectVersion(?Version $version): ProjectVersion
    {
        $this->version = $version;
        return $this;
    }

    public function getVersion(): ?Version
    {
        return $this->version;
    }

    public function setVersion(Version $version): ProjectVersion
    {
        $this->version = $version;
        return $this;
    }

    public function getDateCreated(): ?DateTime
    {
        return $this->dateCreated;
    }

    public function setDateCreated(DateTime $dateCreated): ProjectVersion
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    public function getDateUpdated(): ?DateTime
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(DateTime $dateUpdated): ProjectVersion
    {
        $this->dateUpdated = $dateUpdated;
        return $this;
    }
}
