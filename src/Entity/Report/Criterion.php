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
use Laminas\Form\Annotation;

/**
 * @ORM\Table(name="evaluation_report2_criterion")
 * @ORM\Entity(repositoryClass="Evaluation\Repository\Report\CriterionRepository")
 */
class Criterion extends AbstractEntity
{
    public const INPUT_TYPE_BOOL = 1; // Input type is Yes/No
    public const INPUT_TYPE_STRING = 2; // Input type is a single line textfield
    public const INPUT_TYPE_TEXT = 3; // Input type is a multi-line textarea
    public const INPUT_TYPE_SELECT = 4; // Input type is select box

    protected static array $inputTypeTemplates
        = [
            self::INPUT_TYPE_BOOL   => 'txt-input-type-bool',
            self::INPUT_TYPE_STRING => 'txt-input-type-string',
            self::INPUT_TYPE_TEXT   => 'txt-input-type-text',
            self::INPUT_TYPE_SELECT => 'txt-input-type-select',
        ];

    /**
     * @ORM\Column(name="criterion_id", type="integer", options={"unsigned":true})
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
     * @Annotation\Type("\Laminas\Form\Element\Number")
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
     * @ORM\Column(name="criterion", type="string", nullable=false)
     * @Annotation\Type("\Laminas\Form\Element\Text")
     * @Annotation\Options({
     *     "label":"txt-evaluation-report-criterion-label",
     *     "help-block":"txt-evaluation-report-criterion-help-block"
     * })
     *
     * @var string
     */
    private $criterion;
    /**
     * @ORM\Column(name="help_block", type="text", length=65535, nullable=true)
     * @Annotation\Type("\Laminas\Form\Element\Textarea")
     * @Annotation\Options({
     *     "label":"txt-evaluation-report-criterion-help-block-label",
     *     "help-block":"txt-evaluation-report-criterion-help-block-help-block"
     * })
     *
     * @var string
     */
    private $helpBlock;
    /**
     * @ORM\Column(name="input_type", type="smallint", length=5, options={"unsigned":true}, nullable=false)
     * @Annotation\Type("Laminas\Form\Element\Radio")
     * @Annotation\Attributes({"array":"inputTypeTemplates"})
     * @Annotation\Options({
     *     "label":"txt-evaluation-report-criterion-input-type-label",
     *     "help-block":"txt-evaluation-report-criterion-input-type-help-block"
     * })
     *
     * @var int
     */
    private $inputType = self::INPUT_TYPE_STRING;
    /**
     * @ORM\Column(name="`values`", type="text", length=65535, nullable=true)
     * @Annotation\Type("Laminas\Form\Element\Textarea")
     * @Annotation\Options({
     *     "label":"txt-evaluation-report-criterion-values-label",
     *     "help-block":"txt-evaluation-report-criterion-values-help-block-label"
     * })
     *
     * @var string
     */
    private $values;
    /**
     * @ORM\Column(name="has_score", type="boolean", length=1, nullable=false)
     * @Annotation\Type("Laminas\Form\Element\Checkbox")
     * @Annotation\Options({
     *     "label":"txt-has-score",
     *     "help-block":"txt-evaluation-report-criterion-has-score-help-block"
     * })
     *
     * @var bool
     */
    private $hasScore = true;
    /**
     * @ORM\Column(name="archived", type="boolean", length=1, nullable=false)
     * @Annotation\Type("Laminas\Form\Element\Checkbox")
     * @Annotation\Options({
     *     "label":"txt-archived",
     *     "help-block":"txt-evaluation-report-criterion-archived-help-block"
     * })
     *
     * @var bool
     */
    private $archived = false;
    /**
     * /**
     * @ORM\OneToMany(targetEntity="Evaluation\Entity\Report\Criterion\Version", cascade={"persist"}, mappedBy="criterion")
     * @Annotation\Exclude()
     *
     * @var Collection
     */
    private $versions;
    /**
     * @ORM\ManyToMany(targetEntity="Evaluation\Entity\Report\Type", cascade={"persist", "remove"}, inversedBy="criteria")
     * @ORM\OrderBy=({"sequence"="ASC"})
     * @ORM\JoinTable(name="evaluation_report2_criterion_report_type",
     *    joinColumns={@ORM\JoinColumn(name="criterion_id", referencedColumnName="criterion_id")},
     *    inverseJoinColumns={@ORM\JoinColumn(name="type_id", referencedColumnName="type_id")}
     * )
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntityMultiCheckbox")
     * @Annotation\Options({
     *     "target_class":"Evaluation\Entity\Report\Type",
     *     "label":"txt-evaluation-report-criterion-report-types-label",
     *     "help-block":"txt-evaluation-report-criterion-report-types-help-block"
     * })
     *
     * @var Collection
     */
    private $reportTypes;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
        $this->reportTypes = new ArrayCollection();
    }

    public static function getInputTypeTemplates(): array
    {
        return self::$inputTypeTemplates;
    }

    public function __toString(): string
    {
        return (string)$this->criterion;
    }

    public function addReportTypes(Collection $reportTypes): void
    {
        foreach ($reportTypes as $reportType) {
            $this->reportTypes->add($reportType);
        }
    }

    public function removeReportTypes(Collection $reportTypes): void
    {
        foreach ($reportTypes as $reportType) {
            $this->reportTypes->removeElement($reportType);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Criterion
    {
        $this->id = $id;
        return $this;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): Criterion
    {
        $this->sequence = $sequence;
        return $this;
    }

    public function getCriterion(): ?string
    {
        return $this->criterion;
    }

    public function setCriterion(string $criterion): Criterion
    {
        $this->criterion = $criterion;
        return $this;
    }

    public function getHelpBlock(): ?string
    {
        return $this->helpBlock;
    }

    public function setHelpBlock(string $helpBlock): Criterion
    {
        $this->helpBlock = $helpBlock;
        return $this;
    }

    public function getInputType(): int
    {
        return $this->inputType;
    }

    public function setInputType(int $inputType): Criterion
    {
        $this->inputType = $inputType;
        return $this;
    }

    public function parseInputType(): string
    {
        return self::$inputTypeTemplates[$this->inputType];
    }

    public function getValues(): ?string
    {
        return $this->values;
    }

    public function setValues(string $values): Criterion
    {
        $this->values = $values;
        return $this;
    }

    public function getHasScore(): bool
    {
        return $this->hasScore;
    }

    public function setHasScore(bool $hasScore): Criterion
    {
        $this->hasScore = $hasScore;
        return $this;
    }

    public function getArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): Criterion
    {
        $this->archived = $archived;
        return $this;
    }

    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function setVersions(Collection $versions): Criterion
    {
        $this->versions = $versions;
        return $this;
    }

    public function getReportTypes(): Collection
    {
        return $this->reportTypes;
    }

    public function setReportTypes(Collection $reportTypes): Criterion
    {
        $this->reportTypes = $reportTypes;
        return $this;
    }
}
