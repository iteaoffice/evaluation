<?php

declare(strict_types=1);

namespace EvaluationTest\Service;

use Evaluation\Entity\Report;
use Evaluation\Service\EvaluationReportService;
use Evaluation\View\Helper\Report\Progress;
use Testing\Util\AbstractServiceTest;
use Zend\I18n\Translator\TranslatorInterface;

class ProgressTest extends AbstractServiceTest
{
    public function testInvoke()
    {
        $evaluationReport = new Report();

        $evaluationReportServiceMock = $this->getMockBuilder(EvaluationReportService::class)
            ->disableOriginalConstructor()
            ->setMethods(['parseCompletedPercentage'])
            ->getMock();

        $evaluationReportServiceMock->expects($this->exactly(4))
            ->method('parseCompletedPercentage')
            ->with($evaluationReport)
            ->will($this->onConsecutiveCalls(10.0, 50.0, 100.0, 100.0));

        $translatorMock = $this->getMockBuilder(TranslatorInterface::class)
            ->setMethods(['translate', 'translatePlural'])
            ->getMock();

        $translatorMock->expects($this->exactly(5))
            ->method('translate')
            ->will($this->returnArgument(0));

        $progress = new Progress($evaluationReportServiceMock, $translatorMock);
        $template = $progress->getTemplate();

        $this->assertEquals(
            sprintf($template, 'danger', 10, 10, '10% txt-completed'),
            $progress($evaluationReport)
        );

        $this->assertEquals(
            sprintf($template, 'warning', 50, 50, '50% txt-completed'),
            $progress($evaluationReport)
        );

        $this->assertEquals(
            sprintf($template, 'success', 100, 100, '100% txt-completed'),
            $progress($evaluationReport)
        );

        // Final
        $evaluationReport->setFinal(true);
        $this->assertEquals(
            sprintf($template, 'success', 100, 100, '100% txt-completed + txt-final'),
            $progress($evaluationReport)
        );
    }
}
