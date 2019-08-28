<?php

declare(strict_types=1);

namespace EvaluationTest\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Evaluation\Entity\Report;
use Evaluation\Entity\Reviewer;
use Evaluation\Repository\ReportRepository;
use Evaluation\Repository\ReviewerRepository;
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

        $reviewerRepositoryMock = $this->getMockBuilder(ReviewerRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $evaluationReportRepositoryMock->expects($this->once())
            ->method('findFiltered')
            ->with([])
            ->willReturn($queryBuilderMock);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $map = [
            [$entity, $evaluationReportRepositoryMock],
            [Reviewer::class, $reviewerRepositoryMock]
        ];

        $entityManagerMock->expects($this->exactly(2))
            ->method('getRepository')
            ->will($this->returnValueMap($map));

        $service = new class($entityManagerMock) extends AbstractService {};

        $this->assertEquals($queryBuilderMock, $service->findFiltered($entity, []));
        $this->assertNull($service->findFiltered(Reviewer::class, []));
    }
}
