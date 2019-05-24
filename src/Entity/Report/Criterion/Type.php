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

namespace Evaluation\Entity\Report\Criterion;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Evaluation\Entity\AbstractEntity;
use Zend\Form\Annotation;

/**
 * Evaluation report criterion type
 *
 * @ORM\Table(name="evaluation_report2_criterion_type")
 * @ORM\Entity(repositoryClass="Evaluation\Repository\Report\Criterion\TypeRepository")
 */
class Type extends AbstractEntity
{
    /**
     * @ORM\Column(name="type_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Annotation\Exclude()
     *
     * @var integer
     */
    private $id;
    /**
     * @Gedmo\SortablePosition
     * @ORM\Column(name="sequence", type="integer", options={"unsigned":true})
     * @Annotation\Type("\Zend\Form\Element\Number")
     * @Annotation\Options({
     *     "label":"txt-report-criterion-type-sequence-label",
     *     "help-block":"txt-report-criterion-type-sequence-help-block"
     * })
     *
     * @var integer
     */
    private $sequence = 0;
    /**
     * @ORM\Column(name="type", type="string", length=255, nullable=false)
     * @Annotation\Type("\Zend\Form\Element\Text")
     * @Annotation\Options({
     *     "label":"txt-report-criterion-type-label",
     *     "help-block":"txt-report-criterion-type-help-block"
     * })
     *
     * @var string
     */
    private $type;
    /**
     * @Gedmo\SortableGroup
     * @ORM\ManyToOne(targetEntity="Project\Entity\Evaluation\Report2\Criterion\Category", cascade={"persist"}, inversedBy="types")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="category_id", nullable=false)
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntitySelect")
     * @Annotation\Options({
     *     "target_class":"Project\Entity\Evaluation\Report2\Criterion\Category",
     *     "label":"txt-category",
     *     "help-block":"txt-report-criterion-category-help-block"
     * })
     *
     * @var Category
     */
    private $category;
    /**
     * @ORM\OneToMany(targetEntity="Project\Entity\Evaluation\Report2\Criterion\Version", cascade={"persist"}, mappedBy="type")
     * @Annotation\Exclude()
     *
     * @var Collection
     */
    private $criterionVersions;

    /**
     * Type constructor.
     */
    public function __construct()
    {
        $this->criterionVersions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->type;
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Type
     */
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

    // Used in \Entity\Evaluation\Report2\Criterion\Version to fill the optgroup label with optgroup_identifier
    public function getCategoryLabel(): string
    {
        return (string) $this->category;
    }

    public function setCategory(Category $category): Type
    {
        $this->category = $category;
        return $this;
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
