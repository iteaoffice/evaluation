<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Entity;

use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;
use Project\Entity\Version\Type as VersionType;
use Laminas\Form\Annotation;

/**
 * @ORM\Table(name="evaluation_type")
 * @ORM\Entity
 */
class Type extends AbstractEntity
{
    public const TYPE_PO_EVALUATION = 1;
    public const TYPE_FPP_EVALUATION = 2;
    public const TYPE_FUNDING_STATUS = 3;

    /**
     * @ORM\Column(name="type_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @ORM\Column(name="type", type="string", length=30, nullable=true)
     *
     * @var string
     */
    private $type;
    /**
     * @ORM\OneToMany(targetEntity="Evaluation\Entity\Evaluation", cascade={"persist"}, mappedBy="type")
     * @Annotation\Exclude()
     *
     * @var Evaluation[]
     */
    private $evaluation;

    public function __construct()
    {
        $this->evaluation = new Collections\ArrayCollection();
    }

    /**
     * There is a link between the Evaluation type and the version type, which is currently 1:1
     * I want to avoid a missing link in the feature so I have a dedicated proxy towards the versionType.
     */
    public function getVersionType(): int
    {
        switch ($this->id) {
            case self::TYPE_PO_EVALUATION:
                return VersionType::TYPE_PO;
                break;
            case self::TYPE_FPP_EVALUATION:
                return VersionType::TYPE_FPP;
                break;
            default:
                return self::TYPE_FPP_EVALUATION;
        }
    }

    public function __toString(): string
    {
        return (string)$this->type;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): Type
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

    public function getEvaluation()
    {
        return $this->evaluation;
    }

    public function setEvaluation($evaluation): Type
    {
        $this->evaluation = $evaluation;
        return $this;
    }
}
