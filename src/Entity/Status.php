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

/**
 * EvaluationStatus.
 *
 * @ORM\Table(name="evaluation_status")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @deprecated
 */
class Status extends AbstractEntity
{
    /**
     * @ORM\Column(name="status_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;
    /**
     * @ORM\Column(name="code", type="string", length=3, nullable=true)
     *
     * @var string
     */
    private $code;
    /**
     * @ORM\Column(name="status", type="string", length=32, nullable=true)
     *
     * @var string
     */
    private $status;
    /**
     * @ORM\Column(name="color", type="string", length=6, nullable=true)
     *
     * @var string
     */
    private $color;
    /**
     * @ORM\Column(name="rate_optimistic", type="decimal", precision=10, scale=2, nullable=false)
     *
     * @var float
     */
    private $rateOptimistic;
    /**
     * @ORM\Column(name="rate_pessimistic", type="decimal", precision=10, scale=2, nullable=false)
     *
     * @var float
     */
    private $ratePessimistic;

    /**
     * @ORM\PreUpdate
     */
    public function removeCachedCssFile()
    {
        if (file_exists($this->getCacheCssFileName())) {
            unlink($this->getCacheCssFileName());
        }
    }

    public function getCacheCssFileName(): string
    {
        return __DIR__ . '/../../../../../../public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR
            . (defined('ITEAOFFICE_HOST') ? ITEAOFFICE_HOST : 'test') . DIRECTORY_SEPARATOR . 'css/evaluation-status.css';
    }

    /**
     * Return a normalized CSS name for the type.
     */
    public function parseCssName(): string
    {
        return 'evaluation-status-' . $this->id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $color
     */
    public function setColor($color)
    {
        $this->color = $color;
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
     * @return float
     */
    public function getRateOptimistic()
    {
        return $this->rateOptimistic;
    }

    /**
     * @param float $rateOptimistic
     */
    public function setRateOptimistic($rateOptimistic)
    {
        $this->rateOptimistic = $rateOptimistic;
    }

    /**
     * @return float
     */
    public function getRatePessimistic()
    {
        return $this->ratePessimistic;
    }

    /**
     * @param float $ratePessimistic
     */
    public function setRatePessimistic($ratePessimistic)
    {
        $this->ratePessimistic = $ratePessimistic;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
