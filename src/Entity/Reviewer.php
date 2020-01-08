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
use Doctrine\ORM\Mapping as ORM;
use Evaluation\Entity\Reviewer\Type;
use Project\Entity\Project;
use Laminas\Form\Annotation;

/**
 * @ORM\Table(name="project_review")
 * @ORM\Entity(repositoryClass="Evaluation\Repository\ReviewerRepository")
 */
class Reviewer extends AbstractEntity
{
    /**
     * @ORM\Column(name="review_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var int
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Project\Entity\Project", cascade="persist", inversedBy="reviewers")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="project_id", nullable=false)
     *
     * @var Project
     */
    private $project;
    /**
     * @ORM\ManyToOne(targetEntity="Contact\Entity\Contact", cascade="persist", inversedBy="projectReviewers")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="contact_id", nullable=false)
     * @Annotation\Type("Contact\Form\Element\Contact")
     * @Annotation\Options({"label":"txt-contact"})
     *
     * @var Contact
     */
    private $contact;
    /**
     * @ORM\ManyToOne(targetEntity="Evaluation\Entity\Reviewer\Type", cascade="persist", inversedBy="reviewers")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="type_id")
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntitySelect")
     * @Annotation\Options({
     *     "label":"txt-reviewer-type",
     *     "target_class":"Evaluation\Entity\Reviewer\Type",
     *     "property":"description"
     * })
     *
     * @var Type
     */
    private $type;

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(Contact $contact): Reviewer
    {
        $this->contact = $contact;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Reviewer
    {
        $this->id = $id;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): Reviewer
    {
        $this->project = $project;
        return $this;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function setType(?Type $type): Reviewer
    {
        $this->type = $type;
        return $this;
    }
}
