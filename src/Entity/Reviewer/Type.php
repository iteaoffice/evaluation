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

namespace Evaluation\Entity\Reviewer;

use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;
use Evaluation\Entity\AbstractEntity;
use Evaluation\Entity\Reviewer;
use Laminas\Form\Annotation;

/**
 * @ORM\Table(name="project_review_type")
 * @ORM\Entity
 */
class Type extends AbstractEntity
{
    public const TYPE_PREFERRED = 1;
    public const TYPE_IGNORED = 2;
    public const TYPE_FUTURE_EVALUATION = 3;

    /**
     * @ORM\Column(name="type_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var int
     */
    private $id;
    /**
     * @ORM\Column(name="type", type="string", length=5, nullable=false)
     *
     * @var string
     */
    private $type;
    /**
     * @ORM\Column(name="description", type="string", length=30, nullable=true)
     *
     * @var string
     */
    private $description;
    /**
     * @ORM\OneToMany(targetEntity="Evaluation\Entity\Reviewer", cascade={"persist"}, mappedBy="type")
     * @Annotation\Exclude()
     *
     * @var Reviewer[]|Collections\Collection
     */
    private $reviewers;

    public function __construct()
    {
        $this->reviewers = new Collections\ArrayCollection();
    }

    public function __toString(): string
    {
        return (string)$this->type;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Type
    {
        $this->id = $id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): Type
    {
        $this->description = $description;
        return $this;
    }

    public function getReviewers(): Collections\Collection
    {
        return $this->reviewers;
    }

    public function setReviewers(Collections\Collection $reviewers): Type
    {
        $this->reviewers = $reviewers;
        return $this;
    }
}
