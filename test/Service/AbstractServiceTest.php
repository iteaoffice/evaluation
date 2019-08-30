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

    public function testFindAll()
    {
        $entity = Report::class;

        $evaluationReportRepositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findAll'])
            ->getMock();
        $evaluationReportRepositoryMock->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $entityManagerMock->expects($this->once())
            ->method('getRepository')
            ->with($entity)
            ->willReturn($evaluationReportRepositoryMock);

        $service = new class($entityManagerMock) extends AbstractService {};
        $this->assertEquals([], $service->findAll($entity));
    }

    public function testFind()
    {
        $entity = Report::class;

        $evaluationReportRepositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();
        $evaluationReportRepositoryMock->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $entityManagerMock->expects($this->once())
            ->method('getRepository')
            ->with($entity)
            ->willReturn($evaluationReportRepositoryMock);

        $service = new class($entityManagerMock) extends AbstractService {};
        $this->assertNull($service->find($entity, 1));
    }

    public function testFindByName()
    {
        $entity = Report::class;

        $evaluationReportRepositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $evaluationReportRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => 'pietje'])
            ->willReturn(null);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $entityManagerMock->expects($this->once())
            ->method('getRepository')
            ->with($entity)
            ->willReturn($evaluationReportRepositoryMock);

        $service = new class($entityManagerMock) extends AbstractService {};
        $this->assertNull($service->findByName($entity, 'name' , 'pietje'));
    }
}
