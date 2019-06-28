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

namespace Evaluation\Entity\Reviewer;

use Doctrine\ORM\Mapping as ORM;
use Evaluation\Entity\AbstractEntity;
use Zend\Form\Annotation;

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
     * @var integer
     */
    private $id;
    /**
     * @ORM\Column(name="handle", type="string", length=5, nullable=false)
     * @Annotation\Type("Zend\Form\Element\Text")
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

    public function __get($property)
    {
        return $this->$property;
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }

    public function __isset($property)
    {
        return isset($this->$property);
    }

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
