<?php
/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @category    Evaluation
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2019 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/evaluation for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\Entity\Report\Criterion;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Evaluation\Entity\AbstractEntity;
use Evaluation\Entity\Report\Criterion;
use Evaluation\Entity\Report\Version as ReportVersion;
use Zend\Form\Annotation;

/**
 * Evaluation report criterion version
 *
 * @ORM\Table(name="evaluation_report2_criterion_version", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="criterion_version", columns={"criterion_id", "version_id"})
 * })
 * @ORM\Entity(repositoryClass="Evaluation\Repository\Report\Criterion\VersionRepository")
 */
class Version extends AbstractEntity
{
    /**
     * @ORM\Column(name="criterion_version_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Annotation\Exclude()
     *
     * @var int
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Evaluation\Entity\Report\Criterion", cascade={"persist"}, inversedBy="versions")
     * @ORM\JoinColumn(name="criterion_id", referencedColumnName="criterion_id", nullable=false)
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntitySelect")
     * @Annotation\Options({
     *     "help-block":"txt-evaluation-report-criterion-version-criterion-help-block",
     *     "label":"txt-criterion",
     *     "target_class":"Evaluation\Entity\Report\Criterion",
     *         "find_method":{
     *             "name":"findBy",
     *             "params": {
     *                 "criteria":{"id":0}
     *          }
     *      }
     * })
     *
     * @var Criterion
     */
    private $criterion;
    /**
     * @ORM\ManyToOne(targetEntity="Evaluation\Entity\Report\Version", cascade={"persist"}, inversedBy="criterionVersions")
     * @ORM\JoinColumn(name="version_id", referencedColumnName="version_id", nullable=false)
     * @Annotation\Exclude()
     *
     * @var ReportVersion
     */
    private $reportVersion;
    /**
     * @ORM\ManyToOne(targetEntity="Evaluation\Entity\Report\Criterion\Type", cascade={"persist"}, inversedBy="criterionVersions")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="type_id", nullable=false)
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntitySelect")
     * @Annotation\Options({
     *     "target_class":"Evaluation\Entity\Report\Criterion\Type",
     *     "optgroup_identifier":"categoryLabel",
     *     "help-block":"txt-evaluation-report-criterion-version-type-help-block",
     *     "label":"txt-type"
     * })
     *
     * @var Type
     */
    private $type;
    /**
     *
     * @ORM\Column(name="sequence", type="integer", options={"unsigned":true})
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Options({
     *     "label":"txt-sequence",
     *     "help-block":"txt-evaluation-report-criterion-sequence-help-block"
     * })
     * @Gedmo\SortablePosition
     *
     * @var int
     */
    private $sequence = 0;
    /**
     * @ORM\Column(name="required", type="boolean", length=1, options={"unsigned":true}, nullable=false)
     * @Annotation\Type("Zend\Form\Element\Checkbox")
     * @Annotation\Options({
     *     "label":"txt-required",
     *     "help-block":"txt-evaluation-report-criterion-is-required-help-block"
     * })
     *
     * @var bool
     */
    private $required = true;
    /**
     * @ORM\Column(name="confidential", type="boolean", length=1, options={"unsigned":true}, nullable=false)
     * @Annotation\Type("Zend\Form\Element\Checkbox")
     * @Annotation\Options({
     *     "label":"txt-confidential",
     *     "help-block":"txt-evaluation-report-criterion-is-confidential-help-block"
     * })
     *
     * @var bool
     */
    private $confidential = false;
    /**
     * @ORM\Column(name="highlighted", type="boolean", length=1, options={"unsigned":true}, nullable=false)
     * @Annotation\Type("Zend\Form\Element\Checkbox")
     * @Annotation\Options({
     *     "label":"txt-highlighted",
     *     "help-block":"txt-evaluation-report-criterion-is-highlighted-help-block"
     * })
     *
     * @var bool
     */
    private $highlighted = false;
    /**
     * @ORM\OneToMany(targetEntity="Evaluation\Entity\Report\Criterion\VersionTopic", cascade={"persist","remove"}, mappedBy="criterionVersion", orphanRemoval=true)
     * @Annotation\ComposedObject({
     *     "target_object":"Evaluation\Entity\Report\Criterion\VersionTopic",
     *     "is_collection":"true"
     * })
     * @Annotation\Options({
     *     "allow_add":"true",
     *     "allow_remove":"true",
     *     "count":0,
     *     "label":"txt-topic",
     *     "help-block":"txt-evaluation-report-criterion-version-topic-help-block"
     * })
     *
     * @var Collection
     */
    private $versionTopics;
    /**
     * /**
     * @ORM\OneToMany(targetEntity="Evaluation\Entity\Report\Result", cascade={"persist"}, mappedBy="criterionVersion")
     * @Annotation\Exclude()
     *
     * @var Collection
     */
    private $results;

    public function __construct()
    {
        $this->versionTopics = new ArrayCollection();
    }

    public function __toString(): string
    {
        return ($this->criterion instanceof Criterion) ? (string) $this->criterion : '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Version
    {
        $this->id = $id;
        return $this;
    }

    public function getCriterion(): ?Criterion
    {
        return $this->criterion;
    }

    public function setCriterion(Criterion $criterion): Version
    {
        $this->criterion = $criterion;
        return $this;
    }

    public function getReportVersion(): ?ReportVersion
    {
        return $this->reportVersion;
    }

    public function setReportVersion(ReportVersion $reportVersion): Version
    {
        $this->reportVersion = $reportVersion;
        return $this;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function setType(Type $type): Version
    {
        $this->type = $type;
        return $this;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): Version
    {
        $this->sequence = $sequence;
        return $this;
    }

    public function getRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): Version
    {
        $this->required = $required;
        return $this;
    }

    public function getConfidential(): bool
    {
        return $this->confidential;
    }

    public function setConfidential(bool $confidential): Version
    {
        $this->confidential = $confidential;
        return $this;
    }

    public function getHighlighted(): bool
    {
        return $this->highlighted;
    }

    public function setHighlighted(bool $highlighted): Version
    {
        $this->highlighted = $highlighted;
        return $this;
    }

    public function getVersionTopics(): Collection
    {
        return $this->versionTopics;
    }

    public function setVersionTopics(Collection $versionTopics): Version
    {
        $this->versionTopics = $versionTopics;
        return $this;
    }

    public function addVersionTopics(Collection $versionTopics): void
    {
        foreach ($versionTopics as $versionTopic) {
            $this->versionTopics->add($versionTopic);
        }
    }

    public function removeVersionTopics(Collection $versionTopics): void
    {
        foreach ($versionTopics as $versionTopic) {
            $this->versionTopics->removeElement($versionTopic);
        }
    }

    public function getResults(): Collection
    {
        return $this->results;
    }

    public function setResults(Collection $results): Version
    {
        $this->results = $results;
        return $this;
    }
}
