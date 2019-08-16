<?php

declare(strict_types=1);

namespace EvaluationTest\Service;

use Contact\Entity\Contact;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Entity\Report\Type as EvaluationReportType;
use Evaluation\Entity\Report\Criterion\Version as CriterionVersion;
use Evaluation\Repository\ReportRepository;
use Evaluation\Service\EvaluationReportService;
use Program\Entity\Call\Call;
use Program\Entity\Program;
use Project\Entity\ChangeRequest\Process;
use Project\Entity\Project;
use Project\Entity\Report\Report;
use Project\Entity\Version\Reviewer as VersionReviewer;
use Project\Entity\Report\Reviewer as ReportReviewer;
use Project\Entity\Version\Version;
use Project\Entity\Version\Type as VersionType;
use Testing\Util\AbstractServiceTest;

class EvaluationReportServiceTest extends AbstractServiceTest
{
    /**
     * @var EvaluationReport
     */
    private static $individualVersionEvaluationReport;
    /**
     * @var EvaluationReport
     */
    private static $finalVersionEvaluationReport;
    /**
     * @var EvaluationReport
     */
    private static $individualReportEvaluationReport;
    /**
     * @var EvaluationReport
     */
    private static $finalReportEvaluationReport;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $program = new Program();
        $program->setId(1);
        $program->setProgram('Test program');
        $call = new Call();
        $call->setId(1);
        $call->setCall('Test call');
        $call->setProgram($program);
        $project = new Project();
        $project->setId(1);
        $project->setNumber('12345');
        $project->setProject('Text project');
        $project->setCall($call);

        $reportVersion1 = new EvaluationReport\Version();
        $reportVersion1->setId(1);
        $reportVersion1->setReportType((new EvaluationReportType())->setId(EvaluationReportType::TYPE_PO_VERSION));

        // Set up individual version evaluation report
        $versionReviewer = new VersionReviewer();
        $versionReviewer->setId(1);
        $versionType = new VersionType();
        $versionType->setDescription('Test');
        $version = new Version();
        $version->setId(1);
        $version->setVersionType($versionType);
        $version->setProject($project);
        $version->setReviewers(new ArrayCollection([$versionReviewer]));
        $versionReviewer->setVersion($version);
        $projectVersionReport = new EvaluationReport\ProjectVersion();
        $projectVersionReport->setReviewer($versionReviewer);
        $versionEvaluationReport = new EvaluationReport();
        $versionEvaluationReport->setProjectVersionReport($projectVersionReport);
        $versionEvaluationReport->setVersion($reportVersion1);
        self::$individualVersionEvaluationReport = $versionEvaluationReport;

        // Set up final version evaluation report
        $projectVersionReport = new EvaluationReport\ProjectVersion();
        $projectVersionReport->setVersion($version);
        $versionEvaluationReportFinal = new EvaluationReport();
        $versionEvaluationReportFinal->setProjectVersionReport($projectVersionReport);
        $versionEvaluationReportFinal->setVersion($reportVersion1);
        self::$finalVersionEvaluationReport = $versionEvaluationReportFinal;

        $reportVersion2 = new EvaluationReport\Version();
        $reportVersion2->setId(2);
        $reportVersion2->setReportType((new EvaluationReportType())->setId(EvaluationReportType::TYPE_REPORT));

        // Set up individual report evaluation report
        $reportReviewer = new ReportReviewer();
        $reportReviewer->setId(1);
        $report = new Report();
        $report->setYear(2019);
        $report->setSemester(1);
        $report->setProject($project);
        $report->setReviewers(new ArrayCollection([$reportReviewer]));
        $reportReviewer->setProjectReport($report);
        $projectReportReport = new EvaluationReport\ProjectReport();
        $projectReportReport->setReviewer($reportReviewer);
        $reportEvaluationReport = new EvaluationReport();
        $reportEvaluationReport->setProjectReportReport($projectReportReport);
        $reportEvaluationReport->setVersion($reportVersion2);
        self::$individualReportEvaluationReport = $reportEvaluationReport;

        // Set up final version evaluation report
        $projectReportReport = new EvaluationReport\ProjectReport();
        $projectReportReport->setReport($report);
        $reportEvaluationReportFinal = new EvaluationReport();
        $reportEvaluationReportFinal->setProjectReportReport($projectReportReport);
        $reportEvaluationReportFinal->setVersion($reportVersion2);
        self::$finalReportEvaluationReport = $reportEvaluationReportFinal;
    }

    public function testFindReviewReportsByContact(): void
    {
        $contact =  new Contact();
        $contact->setId(1);

        $repositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findReviewReportsByContact'])
            ->getMock();
        $repositoryMock->expects($this->once())
            ->method('findReviewReportsByContact')
            ->with($contact, EvaluationReportService::STATUS_NEW)
            ->willReturn([]);

        /** @var EntityManager $entityManagerMock */
        $entityManagerMock = $this->getEntityManagerMock(EvaluationReport::class, $repositoryMock);
        $service = new EvaluationReportService($entityManagerMock);
        $reviews = $service->findReviewReportsByContact($contact, EvaluationReportService::STATUS_NEW);
        $this->assertEquals([], $reviews);
    }

    public function testGetReviewers():void
    {
        /** @var EntityManager $entityManagerMock */
        $entityManagerMock = $this->getEntityManagerMock();
        $service = new EvaluationReportService($entityManagerMock);

        // Test empty
        $reviewers = $service->getReviewers(new EvaluationReport());
        $this->assertTrue($reviewers->isEmpty());

        // Get version reviewers for individual report
        $reviewers = $service->getReviewers(self::$individualVersionEvaluationReport);
        $this->assertCount(1, $reviewers);
        /** @var VersionReviewer $reviewer */
        $reviewer = $reviewers->first();
        $this->assertInstanceOf(VersionReviewer::class, $reviewer);
        $this->assertEquals(1, $reviewer->getId());

        // Get version reviewers for final report
        $reviewers = $service->getReviewers(self::$finalVersionEvaluationReport);
        $this->assertCount(1, $reviewers);
        /** @var VersionReviewer $reviewer */
        $reviewer = $reviewers->first();
        $this->assertInstanceOf(VersionReviewer::class, $reviewer);
        $this->assertEquals(1, $reviewer->getId());

        // Get report reviewers for individual report
        $reviewers = $service->getReviewers(self::$individualReportEvaluationReport);
        $this->assertCount(1, $reviewers);
        /** @var ReportReviewer $reviewer */
        $reviewer = $reviewers->first();
        $this->assertInstanceOf(ReportReviewer::class, $reviewer);
        $this->assertEquals(1, $reviewer->getId());

        // Get report reviewers for final report
        $reviewers = $service->getReviewers(self::$finalReportEvaluationReport);
        $this->assertCount(1, $reviewers);
        /** @var ReportReviewer $reviewer */
        $reviewer = $reviewers->first();
        $this->assertInstanceOf(ReportReviewer::class, $reviewer);
        $this->assertEquals(1, $reviewer->getId());
    }

    public function testGetProject()
    {
        // Test empty
        $this->assertTrue(EvaluationReportService::getProject(new EvaluationReport())->isEmpty());

        // Test individual version evaluation report project
        $this->assertEquals(
            1,
            EvaluationReportService::getProject(self::$individualVersionEvaluationReport)->getId()
        );

        // Test final version evaluation report project
        $this->assertEquals(
            1,
            EvaluationReportService::getProject(self::$finalVersionEvaluationReport)->getId()
        );

        // Test individual report evaluation report project
        $this->assertEquals(
            1,
            EvaluationReportService::getProject(self::$individualReportEvaluationReport)->getId()
        );

        // Test final report evaluation report project
        $this->assertEquals(
            1,
            EvaluationReportService::getProject(self::$finalReportEvaluationReport)->getId()
        );
    }

    public function testParseLabel()
    {
        // Test empty
        $this->assertEquals('', EvaluationReportService::parseLabel(new EvaluationReport()));

        $versionLabel = 'Test program Call Test call - 12345 Text project - Test';
        // Test individual version evaluation report label
        $this->assertEquals(
            $versionLabel,
            EvaluationReportService::parseLabel(self::$individualVersionEvaluationReport)
        );

        // Test final version evaluation report label
        $this->assertEquals(
            $versionLabel,
            EvaluationReportService::parseLabel(self::$finalVersionEvaluationReport)
        );

        $reportLabel = 'Test program Call Test call - 12345 Text project - Progress report in 2019 (semester 1)';
        // Test individual report evaluation report label
        $this->assertEquals(
            $reportLabel,
            EvaluationReportService::parseLabel(self::$individualReportEvaluationReport)
        );

        // Test final report evaluation report label
        $this->assertEquals(
            $reportLabel,
            EvaluationReportService::parseLabel(self::$finalReportEvaluationReport)
        );
    }

    public function testParseCompletedPercentage()
    {
        $evaluationReport = self::$finalVersionEvaluationReport;
        $requiredCriterionVersion = new EvaluationReport\Criterion\Version();
        $requiredCriterionVersion->setRequired(true);

        $reportVersion3 = new EvaluationReport\Version();
        $reportVersion3->setId(3);
        $reportVersion3->setReportType((new EvaluationReportType())->setId(EvaluationReportType::TYPE_FPP_VERSION));

        $repositoryMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['count'])
            ->getMock();

        $map = [
            [['reportVersion' => $evaluationReport->getVersion(), 'required' => true], 0],
            [['reportVersion' => $reportVersion3, 'required' => true], 1],
        ];

        $repositoryMock->expects($this->exactly(2))
            ->method('count')
            ->will($this->returnValueMap($map));

        /** @var EntityManager $entityManagerMock */
        $entityManagerMock = $this->getEntityManagerMock(CriterionVersion::class, $repositoryMock);
        $service = new EvaluationReportService($entityManagerMock);

        // Test empty
        $this->assertEquals(0.0, $service->parseCompletedPercentage());

        // Test no results
        $this->assertEquals(0.0, $service->parseCompletedPercentage($evaluationReport));

        // Test no required results
        $result = new EvaluationReport\Result();
        $result->setCriterionVersion($requiredCriterionVersion);
        $result->setValue('test');
        $evaluationReport->getResults()->add($result);
        $this->assertEquals(100.0, $service->parseCompletedPercentage($evaluationReport));

        // Test 1 required result (we need another version as required criteria for a report version are cached)
        $evaluationReport->setVersion($reportVersion3);
        $this->assertEquals(100.0, $service->parseCompletedPercentage($evaluationReport));

        // Cleanup for next test!
        $evaluationReport->setResults(new ArrayCollection());
    }

    public function testGetSortedResults()
    {
        $evaluationReport = clone self::$finalVersionEvaluationReport;
        $result = new EvaluationReport\Result();
        $result->setId(1);
        $evaluationReport->getResults()->add($result);
        $results = [$result];

        $repositoryMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSortedResults'])
            ->getMock();
        $repositoryMock->expects($this->once())
            ->method('getSortedResults')
            ->with($this->equalTo($evaluationReport))
            ->willReturn($results);

        /** @var EntityManager $entityManagerMock */
        $entityManagerMock = $this->getEntityManagerMock(EvaluationReport::class, $repositoryMock);
        $service = new EvaluationReportService($entityManagerMock);

        // Test new results
        $this->assertEquals($results, $service->getSortedResults($evaluationReport));

        // Test existing results
        $evaluationReport->setId(1);
        $this->assertEquals($results, $service->getSortedResults($evaluationReport));
    }

    public function testParseEvaluationReportType()
    {
        $repositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $poVersionType = new VersionType();
        $poVersionType->setId(VersionType::TYPE_PO);

        $fppVersionType = new VersionType();
        $fppVersionType->setId(VersionType::TYPE_FPP);

        $map = [
            [
                ['versionType' => $poVersionType], null,
                (new EvaluationReportType())->setId(EvaluationReportType::TYPE_PO_VERSION)
            ],
            [
                ['versionType' => $fppVersionType], null,
                (new EvaluationReportType())->setId(EvaluationReportType::TYPE_FPP_VERSION)
            ],
        ];

        $repositoryMock
            ->expects($this->exactly(4))
            ->method('findOneBy')
            ->will($this->returnValueMap($map));

        /** @var EntityManager $entityManagerMock */
        $entityManagerMock = $this->getEntityManagerMock(EvaluationReportType::class, $repositoryMock);
        $service = new EvaluationReportService($entityManagerMock);

        // Test invalid type
        $this->assertNull($service->parseEvaluationReportType(new EvaluationReport()));

        // Test project report evaluation report
        $this->assertEquals(
            EvaluationReportType::TYPE_REPORT,
            $service->parseEvaluationReportType(self::$finalReportEvaluationReport)
        );

        // By reference, so this will influence the state of the objects!
        $finalEvaluationReport      = self::$finalVersionEvaluationReport;
        $individualEvaluationReport = self::$individualVersionEvaluationReport;

        // Test PO version final report
        $finalEvaluationReport->getProjectVersionReport()->getProjectVersion()->setVersionType($poVersionType);
        $this->assertEquals(
            EvaluationReportType::TYPE_PO_VERSION,
            $service->parseEvaluationReportType($finalEvaluationReport)
        );
        // Test PO version individual report
        $individualEvaluationReport->getProjectVersionReport()->getReviewer()->getVersion()
            ->setVersionType($poVersionType);
        $this->assertEquals(
            EvaluationReportType::TYPE_PO_VERSION,
            $service->parseEvaluationReportType($individualEvaluationReport)
        );

        // Test FPP version final report
        $finalEvaluationReport->getProjectVersionReport()->getProjectVersion()->setVersionType($fppVersionType);
        $this->assertEquals(
            EvaluationReportType::TYPE_FPP_VERSION,
            $service->parseEvaluationReportType($finalEvaluationReport)
        );
        // Test FPP version individual report
        $individualEvaluationReport->getProjectVersionReport()->getReviewer()->getVersion()
            ->setVersionType($fppVersionType);
        $this->assertEquals(
            EvaluationReportType::TYPE_FPP_VERSION,
            $service->parseEvaluationReportType($individualEvaluationReport)
        );

        // Test change request
        $crVersionType = new VersionType();
        $crVersionType->setId(VersionType::TYPE_CR);
        $crVersion = new Version();
        $crVersion->setVersionType($crVersionType);

        $finalEvaluationReport->getProjectVersionReport()->setProjectVersion($crVersion);
        // test older projects without changerequest process
        $this->assertEquals(
            EvaluationReportType::TYPE_MAJOR_CR_VERSION,
            $service->parseEvaluationReportType($finalEvaluationReport)
        );

        // Test major change request
        $crProcess = new Process();
        $crProcess->setType(Process::TYPE_MAJOR);
        $finalEvaluationReport->getProjectVersionReport()->getProjectVersion()->setChangerequestProcess($crProcess);
        $this->assertEquals(
            EvaluationReportType::TYPE_MAJOR_CR_VERSION,
            $service->parseEvaluationReportType($finalEvaluationReport)
        );

        // Test minor change request
        $crProcess->setType(Process::TYPE_MINOR);
        $this->assertEquals(
            EvaluationReportType::TYPE_MINOR_CR_VERSION,
            $service->parseEvaluationReportType($finalEvaluationReport)
        );
    }

    public function testPrepareEvaluationReport()
    {
        // Setup the data
        $reportReviewer = new ReportReviewer();
        $reportReviewer->setId(1);
        $reportReviewerRepositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();
        $reportReviewerRepositoryMock
            ->method('find')
            ->with($this->equalTo(1))
            ->willReturn($reportReviewer);

        $versionReviewer = new VersionReviewer();
        $versionReviewer->setId(1);
        $versionReviewerRepositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();
        $versionReviewerRepositoryMock
            ->method('find')
            ->with($this->equalTo(1))
            ->willReturn($versionReviewer);

        $evaluationReportRepositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSortedCriterionVersions'])
            ->getMock();

        $criterionVersion = new CriterionVersion();
        $criterionVersion->setId(1);
        $criterionVersion->setCriterion((new EvaluationReport\Criterion())->setId(1)->setHasScore(true));
        $evaluationReportRepositoryMock
            ->method('getSortedCriterionVersions')
            ->willReturn([$criterionVersion]);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $map = [
            [ReportReviewer::class, $reportReviewerRepositoryMock],
            [VersionReviewer::class, $versionReviewerRepositoryMock],
            [EvaluationReport::class, $evaluationReportRepositoryMock],
        ];

        $entityManagerMock->method('getRepository')->will($this->returnValueMap($map));
        /** @var EntityManager $entityManagerMock */
        $service = new EvaluationReportService($entityManagerMock);

        // Test project report evaluation report
        $evaluationReport = $service->prepareEvaluationReport(
            self::$finalReportEvaluationReport->getVersion(),
            1
        );

        $this->assertInstanceOf(
            EvaluationReport\ProjectReport::class,
            $evaluationReport->getProjectReportReport()
        );
        $this->assertEquals(
            $reportReviewer->getId(),
            $evaluationReport->getProjectReportReport()->getReviewer()->getId()
        );
        $this->assertEquals(
            $criterionVersion->getId(),
            $evaluationReport->getResults()->first()->getCriterionVersion()->getId()
        );

        // Test project version evaluation report
        $evaluationReport = $service->prepareEvaluationReport(
            self::$finalVersionEvaluationReport->getVersion(),
            1
        );

        $this->assertInstanceOf(
            EvaluationReport\ProjectVersion::class,
            $evaluationReport->getProjectVersionReport()
        );
        $this->assertEquals(
            $versionReviewer->getId(),
            $evaluationReport->getProjectVersionReport()->getReviewer()->getId()
        );
        $this->assertEquals(
            $criterionVersion->getId(),
            $evaluationReport->getResults()->first()->getCriterionVersion()->getId()
        );

        // Test unknown type


    }
}
