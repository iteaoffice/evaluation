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
use Evaluation\Entity\Report\Criterion\Version as CriterionVersion;
use Gedmo\Mapping\Annotation as Gedmo;
use Laminas\Form\Annotation;

/**
 * Evaluation Report Project Report (This are the real reports)
 *
 * @ORM\Table(name="evaluation_report2_result", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="evaluation_report_criterion_version", columns={"evaluation_report_id", "criterion_version_id"})
 * })
 * @ORM\Entity
 */
class Result extends AbstractEntity
{
    public const SCORE_NOT_EVALUATED = -1;

    protected static array $scoreValues = [
        self::SCORE_NOT_EVALUATED => 'txt-not-evaluated-yet',
        0                         => 'txt-very-low:-unacceptable-or-missing',
        1                         => 'txt-low:-insufficient-lacking-or-inadequate',
        2                         => 'txt-medium:-minimum-required',
        3                         => 'txt-good:-expected-quality',
        4                         => 'txt-excellent:-outstanding-work'
    ];

    protected static array $scoreColors = [
        self::SCORE_NOT_EVALUATED => 'FFFFFF',
        0                         => 'FF0000',
        1                         => 'FF8C00',
        2                         => 'D3D3D3',
        3                         => 'B0E0E6',
        4                         => '008000'
    ];

    /**
     * @ORM\Column(name="result_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var int
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Evaluation\Entity\Report", cascade={"persist"}, inversedBy="results")
     * @ORM\JoinColumn(name="evaluation_report_id", referencedColumnName="evaluation_report_id", nullable=false)
     *
     * @var EvaluationReport
     */
    private $evaluationReport;
    /**
     * @ORM\ManyToOne(targetEntity="Evaluation\Entity\Report\Criterion\Version", cascade={"persist"}, inversedBy="results")
     * @ORM\JoinColumn(name="criterion_version_id", referencedColumnName="criterion_version_id")
     * @ORM\OrderBy({"sequence"="ASC"})
     * @Annotation\Exclude()
     *
     * @var CriterionVersion
     */
    private $criterionVersion;
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
     * @ORM\Column(name="date_changed", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"comment", "value", "score"})
     * @Annotation\Exclude()
     *
     * @var DateTime
     */
    private $dateChanged;
    /**
     * @ORM\Column(name="score", type="smallint", nullable=true)
     * @Annotation\Exclude()
     *
     * @var int
     */
    private $score = self::SCORE_NOT_EVALUATED;
    /**
     * @ORM\Column(name="value", length=65535, type="text", nullable=true)
     * @Annotation\Exclude()
     *
     * @var string
     */
    private $value;
    /**
     * @ORM\Column(name="comment", length=65535, type="text", nullable=true)
     * @Annotation\Exclude()
     *
     * @var string
     */
    private $comment;

    public static function getScoreValues(): array
    {
        return static::$scoreValues;
    }

    public static function getScoreColors(): array
    {
        return static::$scoreColors;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Result
    {
        $this->id = $id;
        return $this;
    }

    public function getEvaluationReport(): ?EvaluationReport
    {
        return $this->evaluationReport;
    }

    public function setEvaluationReport(EvaluationReport $report): Result
    {
        $this->evaluationReport = $report;
        return $this;
    }

    public function getDateCreated(): ?DateTime
    {
        return $this->dateCreated;
    }

    public function setDateCreated(DateTime $dateCreated): Result
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    public function getDateUpdated(): ?DateTime
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(DateTime $dateUpdated): Result
    {
        $this->dateUpdated = $dateUpdated;
        return $this;
    }

    public function getDateChanged(): ?DateTime
    {
        return $this->dateChanged;
    }

    public function setDateChanged(DateTime $dateChanged): Result
    {
        $this->dateChanged = $dateChanged;
        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): Result
    {
        $this->score = $score;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): Result
    {
        $this->value = $value;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): Result
    {
        $this->comment = $comment;
        return $this;
    }

    public function getCriterionVersion(): ?CriterionVersion
    {
        return $this->criterionVersion;
    }

    public function setCriterionVersion(CriterionVersion $criterionVersion): Result
    {
        $this->criterionVersion = $criterionVersion;
        return $this;
    }
}
