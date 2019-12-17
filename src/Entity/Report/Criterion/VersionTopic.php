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

use Doctrine\ORM\Mapping as ORM;
use Evaluation\Entity\AbstractEntity;
use Zend\Form\Annotation;

/**
 * Evaluation report criterion version topic link
 *
 * @ORM\Table(name="evaluation_report2_criterion_version_topic")
 * @ORM\Entity()
 * @Annotation\Instance("Evaluation\Entity\Report\Criterion\VersionTopic")
 */
class VersionTopic extends AbstractEntity
{
    /**
     * @ORM\Column(name="criterion_topic_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Annotation\Exclude()
     *
     * @var int
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Evaluation\Entity\Report\Criterion\Version", cascade={"persist"}, inversedBy="versionTopics")
     * @ORM\JoinColumn(name="criterion_version_id", referencedColumnName="criterion_version_id", nullable=false)
     * @Annotation\Exclude()
     *
     * @var Version
     */
    private $criterionVersion;
    /**
     * @ORM\ManyToOne(targetEntity="Evaluation\Entity\Report\Criterion\Topic", cascade={"persist"}, inversedBy="versionTopics")
     * @ORM\JoinColumn(name="topic_id", referencedColumnName="topic_id", nullable=false)
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntitySelect")
     * @Annotation\Options({
     *     "target_class":"Evaluation\Entity\Report\Criterion\Topic",
     *     "label":"txt-topic"
     * })
     *
     * @var Topic
     */
    private $topic;
    /**
     * @ORM\Column(name="weight", length=5, type="smallint", options={"unsigned":true}, nullable=false)
     * @Annotation\Type("\Zend\Form\Element\Number")
     * @Annotation\Options({"label":"txt-weight"})
     *
     * @var int
     */
    private $weight = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): VersionTopic
    {
        $this->id = $id;
        return $this;
    }

    public function getCriterionVersion(): ?Version
    {
        return $this->criterionVersion;
    }

    public function setCriterionVersion(Version $criterionVersion): VersionTopic
    {
        $this->criterionVersion = $criterionVersion;
        return $this;
    }

    public function getTopic(): ?Topic
    {
        return $this->topic;
    }

    public function setTopic(Topic $topic): VersionTopic
    {
        $this->topic = $topic;
        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): VersionTopic
    {
        $this->weight = $weight;
        return $this;
    }
}
