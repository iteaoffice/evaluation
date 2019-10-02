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

namespace Evaluation\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Zend\Form\Annotation;

/**
 * @ORM\Table(name="evaluation_report2")
 * @ORM\Entity(repositoryClass="Evaluation\Repository\ReportRepository")
 */
class Report extends AbstractEntity
{
    public const SCORE_LOW          = 1; // Low score project
    public const SCORE_MIDDLE_MINUS = 2; // Middle- score project
    public const SCORE_MIDDLE       = 3; // Middle score project
    public const SCORE_MIDDLE_PLUS  = 4; // Middle+ score project
    public const SCORE_TOP          = 5; // Top score project
    public const SCORE_APPROVED     = 6; // Approved project report
    public const SCORE_REJECTED     = 7; // Rejected project report

    public const TYPE_INDIVIDUAL = 'individual'; // Individual review
    public const TYPE_FINAL      = 'final'; // Final review

    private static $versionScores = [
        self::SCORE_TOP          => 'txt-score-top-project',
        self::SCORE_MIDDLE_PLUS  => 'txt-score-middle+',
        self::SCORE_MIDDLE       => 'txt-score-middle',
        self::SCORE_MIDDLE_MINUS => 'txt-score-middle-',
        self::SCORE_LOW          => 'txt-score-low-quality'
    ];

    private static $reportScores = [
        self::SCORE_APPROVED => 'txt-approved',
        self::SCORE_REJECTED => 'txt-rejected'
    ];
    /**
     * @ORM\Column(name="evaluation_report_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Annotation\Exclude()
     *
     * @var int
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Evaluation\Entity\Report\Version", cascade={"persist"}, inversedBy="evaluationReports")
     * @ORM\JoinColumn(name="version_id", referencedColumnName="version_id", nullable=false)
     * @Annotation\Exclude()
     *
     * @var Report\Version
     */
    private $version;
    /**
     * @ORM\Column(name="final", length=1, type="boolean", nullable=false)
     * @Annotation\Type("Zend\Form\Element\Checkbox")
     * @Annotation\Options({
     *     "label":"txt-evaluation-report-final",
     *     "help-block":"txt-evaluation-report-final-help-block"
     * })
     *
     * @var bool
     */
    private $final = false;
    /**
     * @ORM\Column(name="score", type="smallint", length=5, nullable=true)
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Options({
     *     "label":"txt-evaluation-report-score-label",
     *     "help-block":"txt-evaluation-report-score-help-block",
     *     "array":"scores"
     * })
     *
     * @var int
     */
    private $score;
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
     * @ORM\OneToOne(targetEntity="Evaluation\Entity\Report\ProjectReport", cascade={"persist"}, mappedBy="evaluationReport")
     * @Annotation\Exclude()
     *
     * @var Report\ProjectReport
     */
    private $projectReportReport;
    /**
     * @ORM\OneToOne(targetEntity="Evaluation\Entity\Report\ProjectVersion", cascade={"persist"}, mappedBy="evaluationReport")
     * @Annotation\Exclude()
     *
     * @var Report\ProjectVersion
     */
    private $projectVersionReport;
    /**
     * @ORM\OneToMany(targetEntity="Evaluation\Entity\Report\Result", cascade={"persist","remove"}, mappedBy="evaluationReport", orphanRemoval=true)
     * @Annotation\Exclude()
     *
     * @var Report\Result[]|Collection
     */
    private $results;

    public function __construct()
    {
        $this->results = new ArrayCollection();
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }

    public function __isset($property)
    {
        return isset($this->$property);
    }

    public static function getVersionScores(): array
    {
        return self::$versionScores;
    }

    public static function getReportScores(): array
    {
        return self::$reportScores;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Report
    {
        $this->id = $id;
        return $this;
    }

    public function getVersion(): ?Report\Version
    {
        return $this->version;
    }

    public function setVersion(?Report\Version $version): Report
    {
        $this->version = $version;
        return $this;
    }

    public function getFinal(): bool
    {
        return $this->final;
    }

    public function setFinal(bool $final): Report
    {
        $this->final = $final;
        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): Report
    {
        $this->score = $score;
        return $this;
    }

    public function getDateCreated(): ?DateTime
    {
        return $this->dateCreated;
    }

    public function setDateCreated(DateTime $dateCreated): Report
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    public function getDateUpdated(): ?DateTime
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(DateTime $dateUpdated): Report
    {
        $this->dateUpdated = $dateUpdated;
        return $this;
    }

    public function getProjectReportReport(): ?Report\ProjectReport
    {
        return $this->projectReportReport;
    }

    public function setProjectReportReport(?Report\ProjectReport $projectReportReport): Report
    {
        $this->projectReportReport = $projectReportReport;
        return $this;
    }

    public function getProjectVersionReport(): ?Report\ProjectVersion
    {
        return $this->projectVersionReport;
    }

    public function setProjectVersionReport(?Report\ProjectVersion $projectVersionReport): Report
    {
        $this->projectVersionReport = $projectVersionReport;
        return $this;
    }

    public function getResults(): Collection
    {
        return $this->results;
    }

    public function setResults(Collection $results)
    {
        $this->results = $results;
        return $this;
    }
}
