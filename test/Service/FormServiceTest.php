<?php
/**
 * ITEA copyright message placeholder
 *
 * @category    CalendarTest
 * @package     Entity
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2017 ITEA Office (https://itea3.org)
 */

namespace EvaluationTest\Service;

use Calendar\Service\FormService;
use Testing\Util\AbstractServiceTest;

class FormServiceTest extends AbstractServiceTest
{
    public function testCanCreateService(): void
    {
        $formService = new FormService($this->serviceManager, $this->getEntityManagerMock());
        $this->assertInstanceOf(FormService::class, $formService);
    }

}
