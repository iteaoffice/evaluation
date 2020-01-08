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

namespace Evaluation\Entity\Report\Criterion;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Evaluation\Entity\AbstractEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Laminas\Form\Annotation;

/**
 * @ORM\Table(name="evaluation_report2_criterion_category")
 * @ORM\Entity(repositoryClass="Evaluation\Repository\Report\Criterion\CategoryRepository")
 */
class Category extends AbstractEntity
{
    /**
     * @ORM\Column(name="category_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Annotation\Type("\Laminas\Form\Element\Hidden")
     *
     * @var int
     */
    private $id;
    /**
     * @Gedmo\SortablePosition
     * @ORM\Column(name="sequence", type="integer", options={"unsigned":true})
     * @Annotation\Type("\Laminas\Form\Element\Number")
     * @Annotation\Options({"label":"txt-sequence"})
     *
     * @var int
     */
    private $sequence = 0;
    /**
     * @ORM\Column(name="category", type="string", length=255, nullable=false)
     * @Annotation\Type("\Laminas\Form\Element\Text")
     * @Annotation\Options({"label":"txt-category"})
     *
     * @var string
     */
    private $category;

    /**
     * @ORM\OneToMany(targetEntity="Evaluation\Entity\Report\Criterion\Type", cascade={"persist"}, mappedBy="category")
     * @ORM\OrderBy({"sequence"="ASC"})
     * @Annotation\Exclude()
     *
     * @var Collection
     */
    private $types;

    public function __construct()
    {
        $this->types = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string)$this->category;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id): Category
    {
        $this->id = $id;
        return $this;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): Category
    {
        $this->sequence = $sequence;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): Category
    {
        $this->category = $category;

        return $this;
    }

    public function getTypes(): Collection
    {
        return $this->types;
    }

    public function setTypes(Collection $types): Category
    {
        $this->types = $types;
        return $this;
    }
}
