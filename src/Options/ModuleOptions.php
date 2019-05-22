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

final class ModuleOptions extends AbstractOptions
{
    /**
     * @var string
     */
    private $projectTemplate         = '';

    /**
     * @var string
     */
    protected $reportTemplate        = '';

    /**
     * @var array
     */
    protected $presentationTemplates = [];

    /**
     * @var string
     */
    protected $reportAuthor          = '';

    public function getProjectTemplate(): string
    {
        return $this->projectTemplate;
    }

    public function getReportTemplate(): string
    {
        return $this->reportTemplate;
    }

    public function getPresentationTemplates(): array
    {
        return $this->presentationTemplates;
    }

    public function getReportAuthor(): string
    {
        return $this->reportAuthor;
    }
}
