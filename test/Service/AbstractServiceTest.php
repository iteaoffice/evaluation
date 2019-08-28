<?php

declare(strict_types=1);

namespace EvaluationTest\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Evaluation\Entity\Report;
use Evaluation\Repository\ReportRepository;
use Evaluation\Service\AbstractService;
use Testing\Util\AbstractServiceTest as UtilAbstractServiceTest;

class AbstractServiceTest extends UtilAbstractServiceTest
{
    public function testFindFiltered()
    {
        $entity  = Report::class;

        $queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $evaluationReportRepositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findFiltered'])
            ->getMock();

        $evaluationReportRepositoryMock->expects($this->once())
            ->method('findFiltered')
            ->with([])
            ->willReturn($queryBuilderMock);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $entityManagerMock->expects($this->once())
            ->method('getRepository')
            ->with($entity)
            ->willReturn($evaluationReportRepositoryMock);

        $service = new class($entityManagerMock) extends AbstractService {};

        $result = $service->findFiltered($entity, []);
        $this->assertEquals($queryBuilderMock, $result);
    }
}
