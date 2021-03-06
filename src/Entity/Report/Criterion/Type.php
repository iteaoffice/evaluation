<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Entity\Report\Criterion;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Evaluation\Entity\AbstractEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Laminas\Form\Annotation;

/**
 * @ORM\Table(name="evaluation_report2_criterion_type")
 * @ORM\Entity(repositoryClass="Evaluation\Repository\Report\Criterion\TypeRepository")
 */
class Type extends AbstractEntity
{
    /**
     * @ORM\Column(name="type_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Annotation\Type("\Laminas\Form\Element\Hidden")
     *
     * @var int
     * r
     */
    private $id;
    /**
     * @ORM\Column(name="sequence", type="integer", options={"unsigned":true})
     * @Annotation\Type("\Laminas\Form\Element\Number")
     * @Annotation\Options({
     *     "label":"txt-report-criterion-type-sequence-label",
     *     "help-block":"txt-report-criterion-type-sequence-help-block"
     * })
     *
     * @var int
     */
    private $sequence = 0;
    /**
     * @ORM\Column(name="type", type="string", length=255, nullable=false)
     * @Annotation\Type("\Laminas\Form\Element\Text")
     * @Annotation\Options({
     *     "label":"txt-report-criterion-type-label",
     *     "help-block":"txt-report-criterion-type-help-block"
     * })
     *
     * @var string
     */
    private $type;
    /**
     * @ORM\ManyToOne(targetEntity="Evaluation\Entity\Report\Criterion\Category", cascade={"persist"}, inversedBy="types")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="category_id", nullable=false)
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntitySelect")
     * @Annotation\Options({
     *     "target_class":"Evaluation\Entity\Report\Criterion\Category",
     *     "label":"txt-category",
     *     "help-block":"txt-report-criterion-category-help-block"
     * })
     *
     * @var Category
     */
    private $category;
    /**
     * @ORM\OneToMany(targetEntity="Evaluation\Entity\Report\Criterion\Version", cascade={"persist"}, mappedBy="type")
     * @Annotation\Exclude()
     *
     * @var Collection
     */
    private $criterionVersions;

    public function __construct()
    {
        $this->criterionVersions = new ArrayCollection();
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

    public function getSequence(): int
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    // Used in Evaluation\Entity\Report\Criterion\Version to fill the optgroup label with optgroup_identifier

    public function setCategory(Category $category): Type
    {
        $this->category = $category;
        return $this;
    }

    public function getCategoryLabel(): string
    {
        return (string)$this->category;
    }

    public function getCriterionVersions(): Collection
    {
        return $this->criterionVersions;
    }

    public function setCriterionVersions(Collection $criterionVersions): Type
    {
        $this->criterionVersions = $criterionVersions;
        return $this;
    }
}
