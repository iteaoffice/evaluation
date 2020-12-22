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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Evaluation\Entity\AbstractEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Project\Entity\Version\Type as VersionType;
use Laminas\Form\Annotation;

/**
 * @ORM\Table(name="evaluation_report2_type")
 * @ORM\Entity
 */
class Type extends AbstractEntity
{
    public const TYPE_GENERAL_REPORT  = 'report';
    public const TYPE_GENERAL_VERSION = 'version';

    public const TYPE_REPORT           = 1;
    public const TYPE_PO_VERSION       = 2;
    public const TYPE_FPP_VERSION      = 3;
    public const TYPE_MINOR_CR_VERSION = 4;
    public const TYPE_MAJOR_CR_VERSION = 5;

    /**
     * @ORM\Column(name="type_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Annotation\Exclude()
     *
     * @var int
     */
    private $id;
    /**
     *
     * @ORM\Column(name="sequence", type="integer", options={"unsigned":true})
     * @Annotation\Options({
     *     "label":"txt-evaluation-report-type-sequence-label",
     *     "help-block":"txt-evaluation-report-type-sequence-help-block"
     * })
     *
     * @var int
     */
    private $sequence;
    /**
     * @ORM\Column(name="type", type="string", nullable=false)
     * @Annotation\Type("\Laminas\Form\Element\Text")
     * @Annotation\Options({
     *     "label":"txt-evaluation-report-type-type-label",
     *     "help-block":"txt-evaluation-report-type-type-help-block"
     * })
     *
     * @var string
     */
    private $type;
    /**
     * @ORM\Column(name="process_type", type="smallint", options={"unsigned":true}, nullable=true)
     *
     * @var int
     */
    private $processType;
    /**
     * @ORM\ManyToOne(targetEntity="Project\Entity\Version\Type", cascade={"persist"}, inversedBy="evaluationReportType")
     * @ORM\JoinColumn(name="version_type_id", referencedColumnName="type_id", nullable=true)
     * @Annotation\Exclude()
     *
     * @var VersionType
     */
    private $versionType;
    /**
     * @ORM\OneToMany(targetEntity="Evaluation\Entity\Report\Version", cascade={"persist"}, mappedBy="reportType")
     * @Annotation\Exclude()
     *
     * @var Version[]|Collection
     */
    private $reportVersions;
    /**
     * @ORM\ManyToMany(targetEntity="Evaluation\Entity\Report\Criterion", cascade={"persist"}, mappedBy="reportTypes")
     * @Annotation\Exclude()
     *
     * @var Collection
     */
    private $criteria;

    public function __construct()
    {
        $this->reportVersions = new ArrayCollection();
        $this->criteria = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string)$this->type;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Type
    {
        $this->id = $id;
        return $this;
    }

    public function getSequence(): ?int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): Type
    {
        $this->sequence = $sequence;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): Type
    {
        $this->type = $type;
        return $this;
    }

    public function getProcessType(): ?int
    {
        return $this->processType;
    }

    public function setProcessType(int $processType): Type
    {
        $this->processType = $processType;
        return $this;
    }

    public function getVersionType(): ?VersionType
    {
        return $this->versionType;
    }

    public function setVersionType(VersionType $versionType): Type
    {
        $this->versionType = $versionType;
        return $this;
    }

    public function getReportVersions(): Collection
    {
        return $this->reportVersions;
    }

    public function setReportVersions(Collection $reportVersions): Type
    {
        $this->reportVersions = $reportVersions;
        return $this;
    }

    public function getCriteria(): Collection
    {
        return $this->criteria;
    }

    public function setCriteria(Collection $criteria): Type
    {
        $this->criteria = $criteria;
        return $this;
    }
}
