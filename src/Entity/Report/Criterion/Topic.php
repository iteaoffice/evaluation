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
use Gedmo\Mapping\Annotation as Gedmo;
use Evaluation\Entity\AbstractEntity;
use Laminas\Form\Annotation;

/**
 * Evaluation report criterion category
 *
 * @ORM\Table(name="evaluation_report2_criterion_topic")
 * @ORM\Entity(repositoryClass="Evaluation\Repository\Report\Criterion\TopicRepository")
 */
class Topic extends AbstractEntity
{
    /**
     * @ORM\Column(name="topic_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Annotation\Exclude()
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
     * @ORM\Column(name="topic", type="string", length=255, nullable=false)
     * @Annotation\Type("\Laminas\Form\Element\Text")
     * @Annotation\Options({"label":"txt-topic"})
     *
     * @var string
     */
    private $topic;
    /**
     * @ORM\OneToMany(targetEntity="Evaluation\Entity\Report\Criterion\VersionTopic", cascade={"persist"}, mappedBy="topic")
     * @Annotation\Exclude()
     *
     * @var Collection
     */
    private $versionTopics;
    /**
     * @ORM\ManyToMany(targetEntity="Evaluation\Entity\Report\Version", cascade={"persist","remove"}, mappedBy="topics")
     * @Annotation\Exclude()
     *
     * @var Collection
     */
    private $reportVersions;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->versionTopics  = new ArrayCollection();
        $this->reportVersions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->topic;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Topic
    {
        $this->id = $id;
        return $this;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): Topic
    {
        $this->sequence = $sequence;
        return $this;
    }

    public function getTopic(): ?string
    {
        return $this->topic;
    }

    public function setTopic(string $topic): Topic
    {
        $this->topic = $topic;
        return $this;
    }

    public function getVersionTopics(): Collection
    {
        return $this->versionTopics;
    }

    public function setVersionTopics(Collection $versionTopics): Topic
    {
        $this->versionTopics = $versionTopics;
        return $this;
    }

    public function getReportVersions(): Collection
    {
        return $this->reportVersions;
    }

    public function setReportVersions(Collection $reportVersions): Topic
    {
        $this->reportVersions = $reportVersions;
        return $this;
    }

    public function addTopicReportTypes(Collection $reportVersions): void
    {
        foreach ($reportVersions as $reportVersion) {
            $this->reportVersions->add($reportVersion);
        }
    }

    public function removeTopicReportTypes(Collection $reportVersions): void
    {
        foreach ($reportVersions as $reportVersion) {
            $this->reportVersions->removeElement($reportVersion);
        }
    }
}
