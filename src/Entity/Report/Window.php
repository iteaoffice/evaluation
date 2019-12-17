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

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Evaluation\Entity\AbstractEntity;
use Zend\Form\Annotation;

/**
 * Evaluation Report window (Time frame in which the evaluation has to take place)
 *
 * @ORM\Table(name="evaluation_report2_window")
 * @ORM\Entity(repositoryClass="Evaluation\Repository\Report\WindowRepository")
 */
class Window extends AbstractEntity
{
    /**
     * @ORM\Column(name="window_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Annotation\Exclude()
     *
     * @var int
     */
    private $id;
    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     * @Annotation\Type("\Zend\Form\Element\Text")
     * @Annotation\Attributes({})
     * @Annotation\Options({
     *     "label":"txt-title",
     *     "help-block":"txt-evaluation-report-window-title-help-block"
     * })
     *
     * @var string
     */
    private $title;
    /**
     * @ORM\Column(name="description", length=65535, type="text", nullable=true)
     * @Annotation\Type("\Zend\Form\Element\Textarea")
     * @Annotation\Options({
     *     "label":"txt-description",
     *     "help-block":"txt-evaluation-report-window-description-help-block"
     * })
     *
     * @var string
     */
    private $description;
    /**
     * Start date from which a reviewer can create an evaluation report
     *
     * @ORM\Column(name="date_start_report", type="datetime", nullable=false)
     * @Annotation\Type("\Zend\Form\Element\Date")
     * @Annotation\Options({
     *     "label":"txt-evaluation-report-window-start-report-label",
     *     "help-block":"txt-evaluation-report-window-start-report-help-block"
     * })
     *
     * @var DateTime
     */
    private $dateStartReport;
    /**
     * End date until which a reviewer can create an evaluation report
     *
     * @ORM\Column(name="date_end_report", type="datetime", nullable=true)
     * @Annotation\Type("\Zend\Form\Element\Date")
     * @Annotation\Required(false)
     * @Annotation\AllowEmpty(true)
     * @Annotation\Options({
     *     "label":"txt-evaluation-report-window-end-report-label",
     *     "help-block":"txt-evaluation-report-window-end-report-help-block"
     * })
     *
     * @var DateTime
     */
    private $dateEndReport;
    /**
     * Start date for the selection of entities matching the chosen evaluation report type (PPR, PO, FPP, etc.)
     *
     * @ORM\Column(name="date_start_selection", type="datetime", nullable=false)
     * @Annotation\Type("\Zend\Form\Element\Date")
     * @Annotation\Options({
     *     "label":"txt-evaluation-report-window-start-selection-label",
     *     "help-block":"txt-evaluation-report-window-start-selection-help-block"
     * })
     *
     * @var DateTime
     */
    private $dateStartSelection;
    /**
     * End date for the selection of entities matching the chosen evaluation report type (PPR, PO, FPP, etc.)
     *
     * @ORM\Column(name="date_end_selection", type="datetime", nullable=true)
     * @Annotation\Type("\Zend\Form\Element\Date")
     * @Annotation\Required(false)
     * @Annotation\Options({
     *     "label":"txt-evaluation-report-window-end-selection-label",
     *     "help-block":"txt-evaluation-report-window-end-selection-help-block"
     * })
     *
     * @var DateTime
     */
    private $dateEndSelection;
    /**
     * @ORM\ManyToMany(targetEntity="Evaluation\Entity\Report\Version", cascade={"persist"}, inversedBy="windows")
     * @ORM\OrderBy=({"type"="ASC"})
     * @ORM\JoinTable(name="evaluation_report2_window_report_version",
     *    joinColumns={@ORM\JoinColumn(name="window_id", referencedColumnName="window_id")},
     *    inverseJoinColumns={@ORM\JoinColumn(name="version_id", referencedColumnName="version_id")}
     * )
     * @Annotation\Type("DoctrineORMModule\Form\Element\EntityMultiCheckbox")
     * @Annotation\Options({
     *     "target_class":"Evaluation\Entity\Report\Version",
     *     "find_method":{
     *          "name":"findBy",
     *          "params": {
     *              "criteria":{"archived":0}
     *          }
     *      },
     *     "label":"txt-evaluation-report-versions",
     *     "help-block":"txt-evaluation-report-window-report-versions-help-block"
     * })
     *
     * @var Collection
     */
    private $reportVersions;

    public function __construct()
    {
        $this->reportVersions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getTitle();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id): Window
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): Window
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): Window
    {
        $this->description = $description;
        return $this;
    }

    public function getDateStartReport(): ?DateTime
    {
        return $this->dateStartReport;
    }

    public function setDateStartReport(DateTime $dateStartReport): Window
    {
        $this->dateStartReport = $dateStartReport;
        return $this;
    }

    public function getDateEndReport(): ?DateTime
    {
        return $this->dateEndReport;
    }

    public function setDateEndReport(?DateTime $dateEndReport): Window
    {
        $this->dateEndReport = $dateEndReport;
        return $this;
    }

    public function getDateStartSelection(): ?DateTime
    {
        return $this->dateStartSelection;
    }

    public function setDateStartSelection(DateTime $dateStartSelection): Window
    {
        $this->dateStartSelection = $dateStartSelection;
        return $this;
    }

    public function getDateEndSelection(): ?DateTime
    {
        return $this->dateEndSelection;
    }

    public function setDateEndSelection(?DateTime $dateEndSelection): Window
    {
        $this->dateEndSelection = $dateEndSelection;

        return $this;
    }

    public function getReportVersions(): Collection
    {
        return $this->reportVersions;
    }

    public function setReportVersions(Collection $reportVersions): Window
    {
        $this->reportVersions = $reportVersions;
        return $this;
    }

    public function addReportVersions(Collection $reportVersions): void
    {
        foreach ($reportVersions as $reportVersion) {
            $this->reportVersions->add($reportVersion);
        }
    }

    public function removeReportVersions(Collection $reportVersions): void
    {
        foreach ($reportVersions as $reportVersion) {
            $this->reportVersions->removeElement($reportVersion);
        }
    }
}
