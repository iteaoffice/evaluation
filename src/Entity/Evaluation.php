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

namespace Evaluation\Entity;

use Contact\Entity\Contact;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use General\Entity\Country;
use Project\Entity\Funding\Status;
use Project\Entity\Project;
use Laminas\Form\Annotation;

/**
 * @ORM\Table(name="evaluation")
 * @ORM\Entity()
 */
class Evaluation extends AbstractEntity
{
    public const DISPLAY_COST = 4;
    public const DISPLAY_EFFORT = 2;
    public const DISPLAY_EFFORT_PERCENTAGE = 3;
    /**
     * Status constants.
     */
    public const DISPLAY_PARTNERS = 1;
    public const ELIGIBLE_NO = 0;
    public const ELIGIBLE_NOT_SET = 2;
    public const ELIGIBLE_YES = 1;

    protected static array $displayTemplates
        = [
            self::DISPLAY_PARTNERS          => 'txt-partners',
            self::DISPLAY_EFFORT            => 'txt-effort',
            self::DISPLAY_EFFORT_PERCENTAGE => 'txt-effort-percentage',
            self::DISPLAY_COST              => 'txt-cost',
        ];

    protected static array $eligibleTemplates
        = [
            self::ELIGIBLE_YES     => 'txt-eligible',
            self::ELIGIBLE_NO      => 'txt-not-eligible',
            self::ELIGIBLE_NOT_SET => 'txt-eligibility-not-set',
        ];
    /**
     * @ORM\Column(name="evaluation_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Annotation\Exclude()
     *
     * @var int
     */
    private $id;
    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Annotation\Type("\Laminas\Form\Element\Textarea")
     * @Annotation\Attributes({"rows":15})
     * @Annotation\Options({"label":"txt-description","help-block": "txt-evaluation-description-explanation"})
     *
     * @var string
     */
    private $description;
    /**
     * @ORM\Column(name="date_updated", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="update")
     * @Annotation\Exclude()
     *
     * @var DateTime
     */
    private $dateUpdated;
    /**
     * @ORM\ManyToOne(targetEntity="Project\Entity\Funding\Status", cascade="persist", inversedBy="evaluation")
     * @ORM\JoinColumn(name="status_id", referencedColumnName="status_id", nullable=false)
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntitySelect")
     * @Annotation\Options({
     *      "help-block":"txt-evaluation-status-explanation",
     *      "target_class":"Project\Entity\Funding\Status",
     *      "find_method":{
     *          "name":"findBy",
     *          "params": {
     *              "criteria":{"isEvaluation":"1"},
     *              "orderBy":{
     *                  "sequence":"ASC"}
     *              }
     *          }
     *      }
     * )
     * @Annotation\Attributes({"label":"txt-evaluation-status"})
     *
     * @var Status
     */
    private $status;
    /**
     * @ORM\ManyToOne(targetEntity="Contact\Entity\Contact", cascade="persist", inversedBy="evaluation")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="contact_id")
     * @Annotation\Type("Contact\Form\Element\Contact")
     * @Annotation\Attributes({"label":"txt-evaluation-contact-label"})
     * @Annotation\Options({"help-block":"txt-evaluation-contact-help-block"})
     *
     * @var Contact
     */
    private $contact;
    /**
     * @ORM\ManyToOne(targetEntity="General\Entity\Country", cascade="persist", inversedBy="evaluation")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="country_id")
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntitySelect")
     * @Annotation\Options({
     *      "help-block":"txt-evaluation-country-explanation",
     *      "target_class":"General\Entity\Country",
     *      "find_method":{
     *          "name":"findForForm",
     *          "params": {
     *              "criteria":{},
     *              "orderBy":{
     *                  "country":"ASC"}
     *              }
     *          }
     *      }
     * )
     * @Annotation\Attributes({"label":"txt-country"})
     *
     * @var Country
     */
    private $country;
    /**
     * @ORM\ManyToOne(targetEntity="Evaluation\Entity\Type", cascade="persist", inversedBy="evaluation")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="type_id", nullable=false)
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntityRadio")
     * @Annotation\Options({
     *      "help-block":"txt-evaluation-type-explanation",
     *      "target_class":"Evaluation\Entity\Type",
     *      "find_method":{
     *          "name":"findBy",
     *          "params": {
     *              "criteria":{},
     *              "orderBy":{
     *                  "type":"ASC"}
     *              }
     *          }
     *      }
     * )
     * @Annotation\Attributes({"label":"txt-type"})
     *
     * @var Type
     */
    private $type;
    /**
     * @ORM\ManyToOne(targetEntity="Project\Entity\Project", cascade="persist", inversedBy="evaluation")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="project_id", nullable=false)
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntitySelect")
     * @Annotation\Options({
     *      "help-block":"txt-evaluation-project-explanation",
     *      "target_class":"Project\Entity\Project",
     *      "find_method":{
     *          "name":"findBy",
     *          "params": {
     *              "criteria":{},
     *              "orderBy":{
     *                  "call":"ASC"}
     *              }
     *          }
     *      }
     * )
     * @Annotation\Attributes({"label":"txt-project"})
     *
     * @var Project
     */
    private $project;
    /**
     * @ORM\Column(name="eligible", type="smallint", length=2, nullable=true)
     * @Annotation\Type("Laminas\Form\Element\Radio")
     * @Annotation\Attributes({"array":"eligibleTemplates"})
     * @Annotation\Options({"label":"txt-eligibility","help-block":"txt-evaluation-eligibility-explanation"})
     *
     * @var int
     */
    private $eligible;

    public function __construct()
    {
        $this->eligible = self::ELIGIBLE_NOT_SET;
    }

    public static function getEligibleTemplates(): array
    {
        return self::$eligibleTemplates;
    }

    public static function getDisplayTemplates(): array
    {
        return self::$displayTemplates;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Evaluation
    {
        $this->id = $id;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): Evaluation
    {
        $this->description = $description;
        return $this;
    }

    public function getDateUpdated(): ?DateTime
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(DateTime $dateUpdated): Evaluation
    {
        $this->dateUpdated = $dateUpdated;
        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): Evaluation
    {
        $this->contact = $contact;
        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): Evaluation
    {
        $this->country = $country;
        return $this;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function setType(?Type $type): Evaluation
    {
        $this->type = $type;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): Evaluation
    {
        $this->project = $project;
        return $this;
    }

    public function getEligible(bool $textual = false)
    {
        if ($textual) {
            return self::$eligibleTemplates[$this->eligible] ?? '-';
        }

        return $this->eligible;
    }

    public function setEligible($eligible): Evaluation
    {
        $this->eligible = $eligible;

        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): Evaluation
    {
        $this->status = $status;
        return $this;
    }
}
