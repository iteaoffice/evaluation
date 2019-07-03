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

$stylePath = __DIR__ . '/../../../../styles/' . (defined('ITEAOFFICE_HOST') ? ITEAOFFICE_HOST : 'test');

return [
    'evaluation_options' => [
        'projectTemplate'           => $stylePath . '/template/pdf/blank-template-firstpage.pdf',
        'reportTemplate'            => $stylePath . '/template/pdf/evaluation-report-template.pdf',
        'presentationTemplates'     => [
            'title'      => $stylePath . '/template/presentation/title.png',
            'background' => $stylePath . '/template/presentation/background.png',
        ],
        'reportAuthor'              => (defined('ITEAOFFICE_HOST')
                ? strtoupper(ITEAOFFICE_HOST) : 'Test') . ' Office',
    ]
];
