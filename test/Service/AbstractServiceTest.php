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
            ->onlyMethods(['findFiltered'])
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
            ->onlyMethods(['getRepository'])
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
            ->onlyMethods(['findAll'])
            ->getMock();
        $evaluationReportRepositoryMock->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository'])
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
            ->onlyMethods(['find'])
            ->getMock();
        $evaluationReportRepositoryMock->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository'])
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
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $evaluationReportRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => 'pietje'])
            ->willReturn(null);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository'])
            ->getMock();

        $entityManagerMock->expects($this->once())
            ->method('getRepository')
            ->with($entity)
            ->willReturn($evaluationReportRepositoryMock);

        $service = new class($entityManagerMock) extends AbstractService {};
        $this->assertNull($service->findByName($entity, 'name' , 'pietje'));
    }

    public function testCount()
    {
        $entity   = Report::class;
        $criteria = ['x' => 'y'];

        $evaluationReportRepositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['count'])
            ->getMock();
        $evaluationReportRepositoryMock->expects($this->once())
            ->method('count')
            ->with($criteria)
            ->willReturn(1);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository'])
            ->getMock();

        $entityManagerMock->expects($this->once())
            ->method('getRepository')
            ->with($entity)
            ->willReturn($evaluationReportRepositoryMock);

        $service = new class($entityManagerMock) extends AbstractService {};
        $this->assertEquals(1, $service->count($entity, $criteria));
    }

    public function testSave()
    {
        $entity = new Report();

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['contains', 'persist', 'flush'])
            ->getMock();

        $entityManagerMock->expects($this->once())
            ->method('contains')
            ->with($entity)
            ->willReturn(false);

        $entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($entity);

        $entityManagerMock->expects($this->once())->method('flush');

        $service = new class($entityManagerMock) extends AbstractService {};
        $this->assertEquals($entity, $service->save($entity));
    }

    public function testDelete()
    {
        $entity = new Report();

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['remove', 'flush'])
            ->getMock();

        $entityManagerMock->expects($this->once())
            ->method('remove')
            ->with($entity);

        $entityManagerMock->expects($this->once())->method('flush');

        $service = new class($entityManagerMock) extends AbstractService {};
        $this->assertNull($service->delete($entity));
    }

    public function testRefresh()
    {
        $entity = new Report();

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['refresh'])
            ->getMock();

        $entityManagerMock->expects($this->once())
            ->method('refresh')
            ->with($entity);

        $service = new class($entityManagerMock) extends AbstractService {};
        $this->assertNull($service->refresh($entity));
    }
}
