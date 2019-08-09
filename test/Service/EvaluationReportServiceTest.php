<?php

declare(strict_types=1);

namespace EvaluationTest\Service;

use Contact\Entity\Contact;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Repository\ReportRepository;
use Evaluation\Service\EvaluationReportService;
use Program\Entity\Call\Call;
use Program\Entity\Program;
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

    public function setUp(): void
    {
        $this->setUpForEveryTest = false;
        parent::setUp();
    }

    public static function setUpBeforeClass(): void
    {
        $program = new Program();
        $program->setProgram('Test program');
        $call = new Call();
        $call->setCall('Test call');
        $call->setProgram($program);
        $project = new Project();
        $project->setNumber('12345');
        $project->setProject('Text project');
        $project->setCall($call);

        // Set up individual version evaluation report
        $versionReviewer = new VersionReviewer();
        $versionReviewer->setId(1);
        $versionType = new VersionType();
        $versionType->setDescription('Test');
        $version = new Version();
        $version->setVersionType($versionType);
        $version->setReviewers(new ArrayCollection([$versionReviewer]));
        $versionReviewer->setVersion($version);
        $projectVersionReport = new EvaluationReport\ProjectVersion();
        $projectVersionReport->setReviewer($versionReviewer);
        $versionEvaluationReport = new EvaluationReport();
        $versionEvaluationReport->setProjectVersionReport($projectVersionReport);
        self::$individualVersionEvaluationReport = $versionEvaluationReport;

        // Set up final version evaluation report
        $projectVersionReport = new EvaluationReport\ProjectVersion();
        $projectVersionReport->setVersion($version);
        $versionEvaluationReportFinal = new EvaluationReport();
        $versionEvaluationReportFinal->setProjectVersionReport($projectVersionReport);
        self::$finalVersionEvaluationReport = $versionEvaluationReportFinal;

        // Set up individual report evaluation report
        $reportReviewer = new ReportReviewer();
        $reportReviewer->setId(1);
        $report = new Report();
        $report->setYear(2019);
        $report->setSemester(1);
        $report->setReviewers(new ArrayCollection([$reportReviewer]));
        $reportReviewer->setProjectReport($report);
        $projectReportReport = new EvaluationReport\ProjectReport();
        $projectReportReport->setReviewer($reportReviewer);
        $reportEvaluationReport = new EvaluationReport();
        $reportEvaluationReport->setProjectReportReport($projectReportReport);
        self::$individualReportEvaluationReport = $reportEvaluationReport;

        // Set up final version evaluation report
        $projectReportReport = new EvaluationReport\ProjectReport();
        $projectReportReport->setReport($report);
        $reportEvaluationReportFinal = new EvaluationReport();
        $reportEvaluationReportFinal->setProjectReportReport($projectReportReport);
        self::$finalReportEvaluationReport = $reportEvaluationReportFinal;
    }

    public function testFindReviewReportsByContact()
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

    public function testGetReviewers()
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

    public function testParseLabel()
    {
        // Test empty
        $this->assertEquals('', EvaluationReportService::parseLabel(new EvaluationReport()));

        // Test individual version evaluation report label

        // Test final version evaluation report label

        // Test individual report evaluation report label

        // Test final report evaluation report label
    }

}
