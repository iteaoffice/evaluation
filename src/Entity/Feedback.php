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

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Laminas\Form\Annotation;
use Project\Entity\Funding\Status;
use Project\Entity\Version\Version;

/**
 * @ORM\Table(name="evaluation_feedback")
 * @ORM\Entity(repositoryClass="Evaluation\Repository\FeedbackRepository")
 * @Annotation\Hydrator("Laminas\Hydrator\ObjectPropertyHydrator")
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
     * @var int
     */
    private $id;
    /**
     * @ORM\Column(name="date_updated", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="update")
     * @Annotation\Exclude()
     *
     * @var DateTime
     */
    private $dateUpdated;
    /**
     * @ORM\OneToOne(targetEntity="Project\Entity\Version\Version", cascade="persist", inversedBy="feedback")
     * @ORM\JoinColumn(name="version_id", referencedColumnName="version_id", nullable=false)
     * @Annotation\Exclude();
     *
     * @var Version
     */
    private $version;
    /**
     * @ORM\Column(name="mandatory_improvements", type="text", nullable=true)
     * @Annotation\Type("\Laminas\Form\Element\Textarea")
     * @Annotation\Attributes({"rows":10})
     * @Annotation\Options({"label":"txt-mandatory-improvements","help-block": "txt-mandatory-improvements-explanation"})
     *
     * @var string
     */
    private $mandatoryImprovements;
    /**
     * @ORM\Column(name="recommended_improvements", type="text", nullable=true)
     * @Annotation\Type("\Laminas\Form\Element\Textarea")
     * @Annotation\Attributes({"rows":10})
     * @Annotation\Options({"label":"txt-recommended-improvements","help-block": "txt-recommended-improvements-explanation"})
     *
     * @var string
     */
    private $recommendedImprovements;
    /**
     * @ORM\Column(name="review_feedback", type="text", nullable=true)
     * @Annotation\Type("\Laminas\Form\Element\Textarea")
     * @Annotation\Attributes({"rows":10})
     * @Annotation\Options({"label":"txt-review-feedback","help-block": "txt-review-feedback-explanation"})
     *
     * @var string
     */
    private $reviewFeedback;
    /**
     * @ORM\Column(name="evaluation_feedback", type="text", nullable=true)
     * @Annotation\Type("\Laminas\Form\Element\Textarea")
     * @Annotation\Attributes({"rows":10})
     * @Annotation\Options({"label":"txt-evaluation-feedback","help-block": "txt-evaluation-feedback-explanation"})
     *
     * @var string
     */
    private $evaluationFeedback;
    /**
     * @ORM\Column(name="evaluation_conclusion", type="text", nullable=true)
     * @Annotation\Type("\Laminas\Form\Element\Textarea")
     * @Annotation\Attributes({"rows":10})
     * @Annotation\Options({"label":"txt-evaluation-conclusion","help-block": "txt-evaluation-conclusion-explanation"})
     *
     * @var string
     */
    private $evaluationConclusion;
    /**
     * @ORM\ManyToOne(targetEntity="Project\Entity\Funding\Status", cascade="persist", inversedBy="feedback")
     * @ORM\JoinColumn(name="status_id", referencedColumnName="status_id", nullable=false)
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntitySelect")
     * @Annotation\Options({"target_class":"Project\Entity\Funding\Status"})
     * @Annotation\Attributes({"label":"txt-evaluation-status", "required":"true"})
     *
     * @var Status
     */
    private $status;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id): Feedback
    {
        $this->id = $id;
        return $this;
    }

    public function getDateUpdated(): ?DateTime
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(DateTime $dateUpdated): Feedback
    {
        $this->dateUpdated = $dateUpdated;
        return $this;
    }

    public function getVersion(): ?Version
    {
        return $this->version;
    }

    public function setVersion(Version $version): Feedback
    {
        $this->version = $version;
        return $this;
    }

    public function getMandatoryImprovements(): ?string
    {
        return $this->mandatoryImprovements;
    }

    public function setMandatoryImprovements(?string $mandatoryImprovements): Feedback
    {
        $this->mandatoryImprovements = $mandatoryImprovements;
        return $this;
    }

    public function getRecommendedImprovements(): ?string
    {
        return $this->recommendedImprovements;
    }

    public function setRecommendedImprovements(?string $recommendedImprovements): Feedback
    {
        $this->recommendedImprovements = $recommendedImprovements;
        return $this;
    }

    public function getReviewFeedback(): ?string
    {
        return $this->reviewFeedback;
    }

    public function setReviewFeedback(?string $reviewFeedback): Feedback
    {
        $this->reviewFeedback = $reviewFeedback;
        return $this;
    }

    public function getEvaluationFeedback(): ?string
    {
        return $this->evaluationFeedback;
    }

    public function setEvaluationFeedback(string $evaluationFeedback): Feedback
    {
        $this->evaluationFeedback = $evaluationFeedback;
        return $this;
    }

    public function getEvaluationConclusion(): ?string
    {
        return $this->evaluationConclusion;
    }

    public function setEvaluationConclusion(?string $evaluationConclusion): Feedback
    {
        $this->evaluationConclusion = $evaluationConclusion;
        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): Feedback
    {
        $this->status = $status;
        return $this;
    }
}
