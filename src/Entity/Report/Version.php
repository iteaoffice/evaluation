<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Entity\Report;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Evaluation\Entity\AbstractEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Laminas\Form\Annotation;

/**
 * @ORM\Table(name="evaluation_report2_version")
 * @ORM\Entity(repositoryClass="Evaluation\Repository\Report\VersionRepository")
 */
class Version extends AbstractEntity
{
    /**
     * @ORM\Column(name="version_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Annotation\Exclude()
     *
     * @var int
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Evaluation\Entity\Report\Type", cascade={"persist"}, inversedBy="reportVersions")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="type_id", nullable=false)
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntitySelect")
     * @Annotation\Options({
     *     "label":"txt-evaluation-report-type",
     *     "target_class":"Evaluation\Entity\Report\Type",
     *     "help-block":"txt-evaluation-report-type-help-block"
     * })
     *
     * @var Type
     */
    private $reportType;
    /**
     * @ORM\Column(name="label", type="string", nullable=false)
     * @Annotation\Type("\Laminas\Form\Element\Text")
     * @Annotation\Options({
     *     "label":"txt-label",
     *     "help-block":"txt-evaluation-report-version-label-help-block"
     * })
     *
     * @var string
     */
    private $label;
    /**
     * @ORM\Column(name="description", length=65535, type="text", nullable=true)
     * @Annotation\Type("\Laminas\Form\Element\Textarea")
     * @Annotation\Options({
     *     "label":"txt-description",
     *     "help-block":"txt-evaluation-report-version-description-help-block"
     * })
     *
     * @var string
     */
    private $description;
    /**
     * @ORM\Column(name="archived", type="boolean", length=1, nullable=false)
     * @Annotation\Type("Laminas\Form\Element\Checkbox")
     * @Annotation\Options({
     *     "label":"txt-archived",
     *     "help-block":"txt-evaluation-report-version-archived-help-block"
     * })
     *
     * @var bool
     */
    private $archived = false;
    /**
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="create")
     * @Annotation\Exclude()
     *
     * @var DateTime
     */
    private $dateCreated;
    /**
     * @ORM\OneToMany(targetEntity="Evaluation\Entity\Report", cascade={"persist","remove"}, mappedBy="version", orphanRemoval=true)
     * @Annotation\Exclude()
     *
     * @var Collection
     */
    private $evaluationReports;
    /**
     * @ORM\OneToMany(targetEntity="Evaluation\Entity\Report\Criterion\Version", cascade={"persist","remove"}, mappedBy="reportVersion", orphanRemoval=true)
     * @Annotation\Exclude()
     *
     * @var Collection
     */
    private $criterionVersions;
    /**
     * @ORM\ManyToMany(targetEntity="Evaluation\Entity\Report\Criterion\Topic", cascade={"persist","remove"}, inversedBy="reportVersions")
     * @ORM\JoinTable(name="evaluation_report2_criterion_topic_version",
     *      joinColumns={@ORM\JoinColumn(name="version_id", referencedColumnName="version_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="topic_id", referencedColumnName="topic_id")}
     * )
     * @ORM\OrderBy({"sequence" = "ASC"})
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntityMultiCheckbox")
     * @Annotation\Options({
     *     "label":"txt-topics",
     *     "target_class":"Evaluation\Entity\Report\Criterion\Topic",
     *     "find_method":{
     *          "name":"findBy",
     *          "params": {
     *              "criteria":{},
     *              "orderBy":{
     *                  "sequence":"ASC"
     *              }
     *          }
     *      }
     * })
     *
     * @var Collection
     */
    private $topics;

    /**
     * @ORM\ManyToMany(targetEntity="Evaluation\Entity\Report\Window", cascade={"persist","remove"}, mappedBy="reportVersions")
     *
     * @var Collection
     */
    private $windows;

    /**
     * @ORM\OneToMany(targetEntity="Project\Entity\Report\Report", mappedBy="evaluationReportVersion")
     * @Annotation\Exclude()
     *
     * @var Collection
     */
    private $projectReports;

    /**
     * @ORM\OneToMany(targetEntity="Project\Entity\Version\Version", mappedBy="evaluationReportVersion")
     * @Annotation\Exclude()
     *
     * @var Collection
     */
    private $projectVersions;

    public function __construct()
    {
        $this->evaluationReports = new ArrayCollection();
        $this->criterionVersions = new ArrayCollection();
        $this->topics = new ArrayCollection();
        $this->windows = new ArrayCollection();
        $this->projectReports = new ArrayCollection();
        $this->projectVersions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string)$this->label;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Version
    {
        $this->id = $id;
        return $this;
    }

    public function getReportType(): ?Type
    {
        return $this->reportType;
    }

    public function setReportType(Type $reportType): Version
    {
        $this->reportType = $reportType;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): Version
    {
        $this->label = $label;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Version
    {
        $this->description = $description;
        return $this;
    }

    public function getArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): Version
    {
        $this->archived = $archived;
        return $this;
    }

    public function getDateCreated(): ?DateTime
    {
        return $this->dateCreated;
    }

    public function setDateCreated(DateTime $dateCreated): Version
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    public function getEvaluationReports(): Collection
    {
        return $this->evaluationReports;
    }

    public function setEvaluationReports(Collection $evaluationReports): Version
    {
        $this->evaluationReports = $evaluationReports;
        return $this;
    }

    public function getCriterionVersions(): Collection
    {
        return $this->criterionVersions;
    }

    public function setCriterionVersions(Collection $criterionVersions): Version
    {
        $this->criterionVersions = $criterionVersions;
        return $this;
    }

    public function getTopics(): Collection
    {
        return $this->topics;
    }

    public function setTopics(Collection $topics): Version
    {
        $this->topics = $topics;
        return $this;
    }

    public function addTopics(Collection $topics): void
    {
        foreach ($topics as $topic) {
            $this->topics->add($topic);
        }
    }

    public function removeTopics(Collection $topics): void
    {
        foreach ($topics as $topic) {
            $this->topics->removeElement($topic);
        }
    }

    public function getWindows(): Collection
    {
        return $this->windows;
    }

    public function setWindows(Collection $windows): Version
    {
        $this->windows = $windows;
        return $this;
    }

    public function addWindows(Collection $windows): void
    {
        foreach ($windows as $window) {
            $this->windows->add($window);
        }
    }

    public function removeWindows(Collection $windows): void
    {
        foreach ($windows as $window) {
            $this->windows->removeElement($window);
        }
    }

    public function getProjectReports(): Collection
    {
        return $this->projectReports;
    }

    public function setProjectReports(Collection $projectReports): Version
    {
        $this->projectReports = $projectReports;
        return $this;
    }
}
