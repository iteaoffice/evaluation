<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace Evaluation\Entity\Reviewer;

use Doctrine\ORM\Mapping as ORM;
use Evaluation\Entity\AbstractEntity;
use Laminas\Form\Annotation;

/**
 * @ORM\Table(name="project_review_contact")
 * @ORM\Entity(repositoryClass="Evaluation\Repository\Reviewer\ContactRepository")
 */
class Contact extends AbstractEntity
{
    /**
     * @ORM\Column(name="review_contact_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var int
     */
    private $id;
    /**
     * @ORM\Column(name="handle", type="string", length=5, nullable=false)
     * @Annotation\Type("Laminas\Form\Element\Text")
     * @Annotation\Options({
     *     "label":"txt-handle",
     *     "help-block":"txt-review-contact-handle-explanation"
     * })
     *
     * @var string
     */
    private $handle;
    /**
     * @ORM\OneToOne(targetEntity="Contact\Entity\Contact", cascade={"persist"}, inversedBy="projectReviewerContact")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="contact_id", nullable=false)
     * @Annotation\Type("Contact\Form\Element\Contact")
     * @Annotation\Options({"label":"txt-contact"})
     *
     * @var \Contact\Entity\Contact
     */
    private $contact;

    public function __toString(): string
    {
        return (string) $this->getHandle();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Contact
    {
        $this->id = $id;
        return $this;
    }

    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function setHandle(string $handle): Contact
    {
        $this->handle = $handle;
        return $this;
    }

    public function getContact(): ?\Contact\Entity\Contact
    {
        return $this->contact;
    }

    public function setContact(?\Contact\Entity\Contact $contact): Contact
    {
        $this->contact = $contact;
        return $this;
    }
}
