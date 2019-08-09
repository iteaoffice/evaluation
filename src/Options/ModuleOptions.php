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

namespace Evaluation\Options;

use Zend\Stdlib\AbstractOptions;

/**
 * AbstractServiceTest mocks this class, so can't be final
 *
 * Class ModuleOptions
 * @package Evaluation\Options
 */
/*final*/ class ModuleOptions extends AbstractOptions
{
    /**
     * @var string
     */
    protected $reportTemplate = '';
    /**
     * @var array
     */
    protected $presentationTemplates = [];
    /**
     * @var string
     */
    protected $reportAuthor = '';
    /**
     * @var string
     */
    private $projectTemplate = '';

    public function getReportTemplate(): string
    {
        return $this->reportTemplate;
    }

    public function setReportTemplate(string $reportTemplate): void
    {
        $this->reportTemplate = $reportTemplate;
    }

    public function getPresentationTemplates(): array
    {
        return $this->presentationTemplates;
    }

    public function setPresentationTemplates(array $presentationTemplates): void
    {
        $this->presentationTemplates = $presentationTemplates;
    }

    public function getReportAuthor(): string
    {
        return $this->reportAuthor;
    }

    public function setReportAuthor(string $reportAuthor): void
    {
        $this->reportAuthor = $reportAuthor;
    }

    public function getProjectTemplate(): string
    {
        return $this->projectTemplate;
    }

    public function setProjectTemplate(string $projectTemplate): void
    {
        $this->projectTemplate = $projectTemplate;
    }
}
