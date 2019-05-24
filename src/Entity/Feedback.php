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

namespace Evaluation\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Zend\Form\Annotation;

/**
 * Feedback.
 *
 * @ORM\Table(name="evaluation_feedback")
 * @ORM\Entity(repositoryClass="Project\Repository\Evaluation\Feedback")
 * @Annotation\Hydrator("Zend\Hydrator\ObjectProperty")
 * @Annotation\Name("evaluation_feedback")
 */
class Feedback extends AbstractEntity
{
    /**
     * @ORM\Column(name="evaluation_feedback_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Annotation\Exclude()
     *
     * @var integer
     */
    private $id;
    /**
     * @ORM\Column(name="date_updated", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="update")
     * @Annotation\Exclude()
     *
     * @var \DateTime
     */
    private $dateUpdated;
    /**
     * @ORM\OneToOne(targetEntity="Project\Entity\Version\Version", cascade="persist", inversedBy="feedback")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="version_id", referencedColumnName="version_id", nullable=false)
     * })
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntitySelect")
     * @Annotation\Options({
     *      "target_class":"Project\Entity\Version\Version",
     *      "label":"txt-version-type",
     *      "find_method":{
     *          "name":"findBy",
     *          "params": {
     *              "criteria":{},
     *              "orderBy":{
     *                  "dateReviewed":"DESC"}
     *              }
     *          }
     *      }
     * )
     * @Annotation\Attributes({"label":"txt-version", "required":"true"})
     *
     * @var \Project\Entity\Version\Version
     */
    private $version;
    /**
     * @ORM\Column(name="mandatory_improvements", type="text", nullable=true)
     * @Annotation\Type("\Zend\Form\Element\Textarea")
     * @Annotation\Attributes({"rows":10})
     * @Annotation\Options({"label":"txt-mandatory-improvements","help-block": "txt-mandatory-improvements-explanation"})
     *
     * @var string
     */
    private $mandatoryImprovements;
    /**
     * @ORM\Column(name="recommended_improvements", type="text", nullable=true)
     * @Annotation\Type("\Zend\Form\Element\Textarea")
     * @Annotation\Attributes({"rows":10})
     * @Annotation\Options({"label":"txt-recommended-improvements","help-block": "txt-recommended-improvements-explanation"})
     *
     * @var string
     */
    private $recommendedImprovements;
    /**
     * @ORM\Column(name="review_feedback", type="text", nullable=true)
     * @Annotation\Type("\Zend\Form\Element\Textarea")
     * @Annotation\Attributes({"rows":10})
     * @Annotation\Options({"label":"txt-review-feedback","help-block": "txt-review-feedback-explanation"})
     *
     * @var string
     */
    private $reviewFeedback;
    /**
     * @ORM\Column(name="evaluation_feedback", type="text", nullable=true)
     * @Annotation\Type("\Zend\Form\Element\Textarea")
     * @Annotation\Attributes({"rows":10})
     * @Annotation\Options({"label":"txt-evaluation-feedback","help-block": "txt-evaluation-feedback-explanation"})
     *
     * @var string
     */
    private $evaluationFeedback;
    /**
     * @ORM\ManyToOne(targetEntity="Project\Entity\Funding\Status", cascade="persist", inversedBy="feedback")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_id", referencedColumnName="status_id", nullable=false)
     * })
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntitySelect")
     * @Annotation\Options({"target_class":"Project\Entity\Funding\Status"})
     * @Annotation\Attributes({"label":"txt-evaluation-status", "required":"true"})
     *
     * @var \Project\Entity\Funding\Status
     */
    private $status;

    /**
     * Magic Getter.
     *
     * @param $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        return $this->$property;
    }

    /**
     * Magic Setter.
     *
     * @param $property
     * @param $value
     */
    public function __set($property, $value)
    {
        $this->$property = $value;
    }

    /**
     * @param $property
     *
     * @return bool
     */
    public function __isset($property)
    {
        return isset($this->$property);
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
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return \DateTime
     */
    public function getDateUpdated()
    {
        return $this->dateUpdated;
    }

    /**
     * @param \DateTime $dateUpdated
     */
    public function setDateUpdated($dateUpdated)
    {
        $this->dateUpdated = $dateUpdated;
    }

    /**
     * @return \Project\Entity\Version\Version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param \Project\Entity\Version\Version $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getMandatoryImprovements()
    {
        return $this->mandatoryImprovements;
    }

    /**
     * @param string $mandatoryImprovements
     */
    public function setMandatoryImprovements($mandatoryImprovements)
    {
        $this->mandatoryImprovements = $mandatoryImprovements;
    }

    /**
     * @return string
     */
    public function getRecommendedImprovements()
    {
        return $this->recommendedImprovements;
    }

    /**
     * @param string $recommendedImprovements
     */
    public function setRecommendedImprovements($recommendedImprovements)
    {
        $this->recommendedImprovements = $recommendedImprovements;
    }

    /**
     * @return string
     */
    public function getReviewFeedback()
    {
        return $this->reviewFeedback;
    }

    /**
     * @param string $reviewFeedback
     */
    public function setReviewFeedback($reviewFeedback)
    {
        $this->reviewFeedback = $reviewFeedback;
    }

    /**
     * @return string
     */
    public function getEvaluationFeedback()
    {
        return $this->evaluationFeedback;
    }

    /**
     * @param string $evaluationFeedback
     */
    public function setEvaluationFeedback($evaluationFeedback)
    {
        $this->evaluationFeedback = $evaluationFeedback;
    }

    /**
     * @return \Project\Entity\Funding\Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param \Project\Entity\Funding\Status $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
