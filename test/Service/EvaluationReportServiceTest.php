<?php

/**
 * ITEA Office all rights reserved
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2021 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 */

declare(strict_types=1);

namespace EvaluationTest\Service;

use Contact\Entity\Contact;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Evaluation\Entity\Report as EvaluationReport;
use Evaluation\Entity\Report\Type as EvaluationReportType;
use Evaluation\Entity\Report\Criterion\Version as CriterionVersion;
use Evaluation\Entity\Report\Criterion\Type as CriterionType;
use Evaluation\Repository\Report\Criterion\VersionRepository as CriterionVersionRepository;
use Evaluation\Repository\Report\VersionRepository;
use Evaluation\Repository\ReportRepository;
use Evaluation\Service\EvaluationReportService;
use Program\Entity\Call\Call;
use Program\Entity\Program;
use Project\Entity\ChangeRequest\Process;
use Project\Entity\Project;
use Project\Entity\Report\Report;
use Project\Entity\Version\Reviewer;
use Project\Entity\Version\Reviewer as VersionReviewer;
use Project\Entity\Report\Reviewer as ReportReviewer;
use Project\Entity\Version\Version;
use Project\Entity\Version\Type as VersionType;
use Testing\Util\AbstractServiceTest;

class EvaluationReportServiceTest extends AbstractServiceTest
{
    private static EvaluationReport $individualVersionEvaluationReport;
    private static EvaluationReport $finalVersionEvaluationReport;
    private static EvaluationReport $individualReportEvaluationReport;
    private static EvaluationReport $finalReportEvaluationReport;

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
        $contact = new Contact();
        $contact->setId(1);

        $repositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findReviewReportsByContact'])
            ->getMock();
        $repositoryMock->expects(self::once())
            ->method('findReviewReportsByContact')
            ->with($contact, EvaluationReportService::STATUS_NEW)
            ->willReturn([]);

        /** @var EntityManager $entityManagerMock */
        $entityManagerMock = $this->getEntityManagerMock(EvaluationReport::class, $repositoryMock);
        $service = new EvaluationReportService($entityManagerMock);
        $reviews = $service->findReviewReportsByContact($contact, EvaluationReportService::STATUS_NEW);
        self::assertEquals([], $reviews);
    }

    public function testGetReviewers(): void
    {
        /** @var EntityManager $entityManagerMock */
        $entityManagerMock = $this->getEntityManagerMock();
        $service = new EvaluationReportService($entityManagerMock);

        // Test empty
        $reviewers = $service->getReviewers(new EvaluationReport());
        self::assertTrue($reviewers->isEmpty());

        // Get version reviewers for individual report
        $reviewers = $service->getReviewers(self::$individualVersionEvaluationReport);
        self::assertCount(1, $reviewers);
        /** @var VersionReviewer $reviewer */
        $reviewer = $reviewers->first();
        self::assertInstanceOf(VersionReviewer::class, $reviewer);
        self::assertEquals(1, $reviewer->getId());

        // Get version reviewers for final report
        $reviewers = $service->getReviewers(self::$finalVersionEvaluationReport);
        self::assertCount(1, $reviewers);
        /** @var VersionReviewer $reviewer */
        $reviewer = $reviewers->first();
        self::assertInstanceOf(VersionReviewer::class, $reviewer);
        self::assertEquals(1, $reviewer->getId());

        // Get report reviewers for individual report
        $reviewers = $service->getReviewers(self::$individualReportEvaluationReport);
        self::assertCount(1, $reviewers);
        /** @var ReportReviewer $reviewer */
        $reviewer = $reviewers->first();
        self::assertInstanceOf(ReportReviewer::class, $reviewer);
        self::assertEquals(1, $reviewer->getId());

        // Get report reviewers for final report
        $reviewers = $service->getReviewers(self::$finalReportEvaluationReport);
        self::assertCount(1, $reviewers);
        /** @var ReportReviewer $reviewer */
        $reviewer = $reviewers->first();
        self::assertInstanceOf(ReportReviewer::class, $reviewer);
        self::assertEquals(1, $reviewer->getId());
    }

    public function testGetProject(): void
    {
        // Test empty
        self::assertTrue(EvaluationReportService::getProject(new EvaluationReport())->isEmpty());

        // Test individual version evaluation report project
        self::assertEquals(
            1,
            EvaluationReportService::getProject(self::$individualVersionEvaluationReport)->getId()
        );

        // Test final version evaluation report project
        self::assertEquals(
            1,
            EvaluationReportService::getProject(self::$finalVersionEvaluationReport)->getId()
        );

        // Test individual report evaluation report project
        self::assertEquals(
            1,
            EvaluationReportService::getProject(self::$individualReportEvaluationReport)->getId()
        );

        // Test final report evaluation report project
        self::assertEquals(
            1,
            EvaluationReportService::getProject(self::$finalReportEvaluationReport)->getId()
        );
    }

    public function testParseLabel(): void
    {
        // Test empty
        self::assertEquals('', EvaluationReportService::parseLabel(new EvaluationReport()));

        $versionLabel = 'Test call - 12345 Text project - Test';
        // Test individual version evaluation report label
        self::assertEquals(
            $versionLabel,
            EvaluationReportService::parseLabel(self::$individualVersionEvaluationReport)
        );

        // Test final version evaluation report label
        self::assertEquals(
            $versionLabel,
            EvaluationReportService::parseLabel(self::$finalVersionEvaluationReport)
        );

        $reportLabel = 'Test call - 12345 Text project - Progress report in 2019 (semester 1)';
        // Test individual report evaluation report label
        self::assertEquals(
            $reportLabel,
            EvaluationReportService::parseLabel(self::$individualReportEvaluationReport)
        );

        // Test final report evaluation report label
        self::assertEquals(
            $reportLabel,
            EvaluationReportService::parseLabel(self::$finalReportEvaluationReport)
        );
    }

    public function testParseCompletedPercentage(): void
    {
        $evaluationReport = self::$finalVersionEvaluationReport;
        $requiredCriterionVersion = new EvaluationReport\Criterion\Version();
        $requiredCriterionVersion->setRequired(true);

        $reportVersion3 = new EvaluationReport\Version();
        $reportVersion3->setId(3);
        $reportVersion3->setReportType((new EvaluationReportType())->setId(EvaluationReportType::TYPE_FPP_VERSION));

        $repositoryMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->addMethods(['count'])
            ->getMock();

        $map = [
            [['reportVersion' => $evaluationReport->getVersion(), 'required' => true], 0],
            [['reportVersion' => $reportVersion3, 'required' => true], 1],
        ];

        $repositoryMock->expects(self::exactly(2))
            ->method('count')
            ->will(self::returnValueMap($map));

        /** @var EntityManager $entityManagerMock */
        $entityManagerMock = $this->getEntityManagerMock(CriterionVersion::class, $repositoryMock);
        $service = new EvaluationReportService($entityManagerMock);

        // Test empty
        self::assertEquals(0.0, $service->parseCompletedPercentage());

        // Test no results
        self::assertEquals(0.0, $service->parseCompletedPercentage($evaluationReport));

        // Test no required results
        $result = new EvaluationReport\Result();
        $result->setCriterionVersion($requiredCriterionVersion);
        $result->setValue('test');
        $evaluationReport->getResults()->add($result);
        self::assertEquals(100.0, $service->parseCompletedPercentage($evaluationReport));

        // Test 1 required result (we need another version as required criteria for a report version are cached)
        $evaluationReport->setVersion($reportVersion3);
        self::assertEquals(100.0, $service->parseCompletedPercentage($evaluationReport));

        // Cleanup for next test!
        $evaluationReport->setResults(new ArrayCollection());
    }

    public function testGetSortedResults(): void
    {
        $evaluationReport = clone self::$finalVersionEvaluationReport;
        $result = new EvaluationReport\Result();
        $result->setId(1);
        $evaluationReport->getResults()->add($result);
        $results = [$result];

        $repositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSortedResults'])
            ->getMock();
        $repositoryMock->expects(self::once())
            ->method('getSortedResults')
            ->with(self::equalTo($evaluationReport))
            ->willReturn($results);

        /** @var EntityManager $entityManagerMock */
        $entityManagerMock = $this->getEntityManagerMock(EvaluationReport::class, $repositoryMock);
        $service = new EvaluationReportService($entityManagerMock);

        // Test new results
        self::assertEquals($results, $service->getSortedResults($evaluationReport));

        // Test existing results
        $evaluationReport->setId(1);
        self::assertEquals($results, $service->getSortedResults($evaluationReport));
    }

    public function testParseEvaluationReportType(): void
    {
        $repositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
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
            ->expects(self::exactly(4))
            ->method('findOneBy')
            ->will(self::returnValueMap($map));

        /** @var EntityManager $entityManagerMock */
        $entityManagerMock = $this->getEntityManagerMock(EvaluationReportType::class, $repositoryMock);
        $service = new EvaluationReportService($entityManagerMock);

        // Test invalid type
        self::assertNull($service->parseEvaluationReportType(new EvaluationReport()));

        // Test project report evaluation report
        self::assertEquals(
            EvaluationReportType::TYPE_REPORT,
            $service->parseEvaluationReportType(self::$finalReportEvaluationReport)
        );

        // By reference, so this will influence the state of the objects!
        $finalEvaluationReport      = self::$finalVersionEvaluationReport;
        $individualEvaluationReport = self::$individualVersionEvaluationReport;

        // Test PO version final report
        $finalEvaluationReport->getProjectVersionReport()->getProjectVersion()->setVersionType($poVersionType);
        self::assertEquals(
            EvaluationReportType::TYPE_PO_VERSION,
            $service->parseEvaluationReportType($finalEvaluationReport)
        );
        // Test PO version individual report
        $individualEvaluationReport->getProjectVersionReport()->getReviewer()->getVersion()
            ->setVersionType($poVersionType);
        self::assertEquals(
            EvaluationReportType::TYPE_PO_VERSION,
            $service->parseEvaluationReportType($individualEvaluationReport)
        );

        // Test FPP version final report
        $finalEvaluationReport->getProjectVersionReport()->getProjectVersion()->setVersionType($fppVersionType);
        self::assertEquals(
            EvaluationReportType::TYPE_FPP_VERSION,
            $service->parseEvaluationReportType($finalEvaluationReport)
        );
        // Test FPP version individual report
        $individualEvaluationReport->getProjectVersionReport()->getReviewer()->getVersion()
            ->setVersionType($fppVersionType);
        self::assertEquals(
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
        self::assertEquals(
            EvaluationReportType::TYPE_MAJOR_CR_VERSION,
            $service->parseEvaluationReportType($finalEvaluationReport)
        );

        // Test major change request
        $crProcess = new Process();
        $crProcess->setType(Process::TYPE_MAJOR);
        $finalEvaluationReport->getProjectVersionReport()->getProjectVersion()->setChangerequestProcess($crProcess);
        self::assertEquals(
            EvaluationReportType::TYPE_MAJOR_CR_VERSION,
            $service->parseEvaluationReportType($finalEvaluationReport)
        );

        // Test minor change request
        $crProcess->setType(Process::TYPE_MINOR);
        self::assertEquals(
            EvaluationReportType::TYPE_MINOR_CR_VERSION,
            $service->parseEvaluationReportType($finalEvaluationReport)
        );
    }

    public function testPrepareEvaluationReport(): void
    {
        // Setup the data
        $reportReviewer = new ReportReviewer();
        $reportReviewer->setId(1);
        $reportReviewerRepositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $reportReviewerRepositoryMock
            ->method('find')
            ->with(self::equalTo(1))
            ->willReturn($reportReviewer);

        $versionReviewer = new VersionReviewer();
        $versionReviewer->setId(1);
        $versionReviewerRepositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $versionReviewerRepositoryMock
            ->method('find')
            ->with(self::equalTo(1))
            ->willReturn($versionReviewer);

        $evaluationReportRepositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSortedCriterionVersions'])
            ->getMock();

        $criterionWithValues = new EvaluationReport\Criterion();
        $criterionWithValues->setId(1)
            ->setHasScore(true)
            ->setValues('{"1","2"}');

        $criterionVersionWithValues = new CriterionVersion();
        $criterionVersionWithValues->setId(1);
        $criterionVersionWithValues->setCriterion($criterionWithValues);
        $criterionVersionWithValues->setDefaultValue('test');

        $criterionVersionWithComment = new CriterionVersion();
        $criterionVersionWithComment->setId(2);
        $criterionVersionWithComment->setCriterion((new EvaluationReport\Criterion())->setId(2)->setHasScore(true));
        $criterionVersionWithComment->setDefaultValue('test');

        $evaluationReportRepositoryMock
            ->method('getSortedCriterionVersions')
            ->willReturn([$criterionVersionWithValues, $criterionVersionWithComment]);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository'])
            ->getMock();

        $map = [
            [ReportReviewer::class, $reportReviewerRepositoryMock],
            [VersionReviewer::class, $versionReviewerRepositoryMock],
            [EvaluationReport::class, $evaluationReportRepositoryMock],
        ];

        $entityManagerMock->method('getRepository')->will(self::returnValueMap($map));
        /** @var EntityManager $entityManagerMock */
        $service = new EvaluationReportService($entityManagerMock);

        // Test project report evaluation report with default values
        $evaluationReport = $service->prepareEvaluationReport(
            self::$finalReportEvaluationReport->getVersion(),
            1
        );

        self::assertInstanceOf(
            EvaluationReport\ProjectReport::class,
            $evaluationReport->getProjectReportReport()
        );
        self::assertEquals(
            $reportReviewer->getId(),
            $evaluationReport->getProjectReportReport()->getReviewer()->getId()
        );
        self::assertEquals(
            $criterionVersionWithValues->getId(),
            $evaluationReport->getResults()->first()->getCriterionVersion()->getId()
        );
        self::assertEquals(
            $criterionVersionWithValues->getDefaultValue(),
            $evaluationReport->getResults()->first()->getValue()
        );
        self::assertEquals(
            $criterionVersionWithValues->getDefaultValue(),
            $evaluationReport->getResults()->get(1)->getComment()
        );

        // Test project version evaluation report
        $evaluationReport = $service->prepareEvaluationReport(
            self::$finalVersionEvaluationReport->getVersion(),
            1
        );

        self::assertInstanceOf(
            EvaluationReport\ProjectVersion::class,
            $evaluationReport->getProjectVersionReport()
        );
        self::assertEquals(
            $versionReviewer->getId(),
            $evaluationReport->getProjectVersionReport()->getReviewer()->getId()
        );
        self::assertEquals(
            $criterionVersionWithValues->getId(),
            $evaluationReport->getResults()->first()->getCriterionVersion()->getId()
        );

        // Test unknown type
        $unknownReportType = new EvaluationReportType();
        $unknownReportType->setId(0);
        $reportVersion = new EvaluationReport\Version();
        $reportVersion->setId(1);
        $reportVersion->setReportType($unknownReportType);
        $evaluationReport = $service->prepareEvaluationReport($reportVersion, 1);

        self::assertNull($evaluationReport->getProjectVersionReport());
        self::assertNull($evaluationReport->getProjectReportReport());
        self::assertEquals(1, $evaluationReport->getVersion()->getId());
    }

    public function testPreFillFppReport(): void
    {
        $matchingCriterion = new EvaluationReport\Criterion();
        $matchingCriterion->setId(1);
        $project = new Project();

        $poCriterionVersion = new EvaluationReport\Criterion\Version();
        $poCriterionVersion->setCriterion($matchingCriterion);
        $poResult = new EvaluationReport\Result();
        $poResult->setId(1);
        $poResult->setScore(1);
        $poResult->setCriterionVersion($poCriterionVersion);
        $poEvaluationReport = new EvaluationReport();
        $poEvaluationReport->setScore(EvaluationReport::SCORE_TOP);
        $poEvaluationReport->getResults()->add($poResult);
        $poProjectVersionReport = new EvaluationReport\ProjectVersion();
        $poProjectVersionReport->setEvaluationReport($poEvaluationReport);
        $poVersionType = new VersionType();
        $poVersionType->setId(VersionType::TYPE_PO);
        $poVersion = new Version();
        $poVersion->setVersionType($poVersionType);
        $poVersion->setProject($project);
        $poVersion->setProjectVersionReport($poProjectVersionReport);
        $project->getVersion()->add($poVersion);

        $fppVersionType = new VersionType();
        $fppVersionType->setId(VersionType::TYPE_FPP);
        $fppVersion = new Version();
        $fppVersion->setVersionType($fppVersionType);
        $fppVersion->setProject($project);
        $project->getVersion()->add($fppVersion);
        $fppProjectVersionReport = new EvaluationReport\ProjectVersion();
        $fppReviewer = new Reviewer();
        $fppReviewer->setVersion($fppVersion);
        $fppReviewer->setProjectVersionReport($fppProjectVersionReport);
        $fppProjectVersionReport->setReviewer($fppReviewer);
        $fppReportType = new EvaluationReportType();
        $fppReportType->setId(EvaluationReportType::TYPE_FPP_VERSION);
        $fppReportVersion = new EvaluationReport\Version();
        $fppReportVersion->setReportType($fppReportType);
        $fppCriterionVersion = new EvaluationReport\Criterion\Version();
        $fppCriterionVersion->setCriterion($matchingCriterion);
        $fppResult = new EvaluationReport\Result();
        $fppResult->setCriterionVersion($fppCriterionVersion);
        $fppEvaluationReport = new EvaluationReport();
        $fppEvaluationReport->setVersion($fppReportVersion);
        $fppEvaluationReport->setProjectVersionReport($fppProjectVersionReport);
        $fppEvaluationReport->getResults()->add($fppResult);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManager $entityManagerMock */
        $service = new EvaluationReportService($entityManagerMock);

        $service->preFillFppReport($fppEvaluationReport);

        self::assertEquals(EvaluationReport::SCORE_TOP, $fppEvaluationReport->getScore());
        /** @var EvaluationReport\Result $fppResult */
        $fppResult = $fppEvaluationReport->getResults()->first();
        self::assertInstanceOf(EvaluationReport\Result::class, $fppResult);
        self::assertEquals(1, $fppResult->getScore());
    }

    public function testCopyEvaluationReportVersion(): void
    {
        $criterionVersion = new EvaluationReport\Criterion\Version();
        $criterionVersion->setId(1);
        $criterionVersion->setCriterion((new EvaluationReport\Criterion())->setId(1));
        $criterionVersion->setType((new EvaluationReport\Criterion\Type())->setId(1));
        $criterionVersion->setHighlighted(true);
        $criterionVersion->setSequence(3);
        $criterionVersion->setRequired(true);
        $criterionVersion->setConfidential(true);
        $criterionVersion->setReportVersion((new EvaluationReport\Version())->setId(1));
        $criterionVersion->getVersionTopics()->add((new EvaluationReport\Criterion\VersionTopic())->setId(1));

        $evaluationReportVersion = new EvaluationReport\Version();
        $evaluationReportVersion->setLabel('Test');
        $evaluationReportVersion->setReportType((new EvaluationReportType())->setId(1));
        $evaluationReportVersion->getCriterionVersions()->add($criterionVersion);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManager $entityManagerMock */
        $service = new EvaluationReportService($entityManagerMock);

        $copy = $service->copyEvaluationReportVersion($evaluationReportVersion);

        self::assertCount(1, $copy->getCriterionVersions());
        /** @var EvaluationReport\Criterion\Version $criterionVersionCopy */
        $criterionVersionCopy = $copy->getCriterionVersions()->first();
        self::assertNotEquals(1, $criterionVersionCopy->getId());
        self::assertEquals(1, $criterionVersionCopy->getCriterion()->getId());
        self::assertEquals(1, $criterionVersionCopy->getType()->getId());
        self::assertNotEquals(1, $criterionVersionCopy->getReportVersion()->getId());
        self::assertEquals(3, $criterionVersionCopy->getSequence());
        self::assertTrue($criterionVersionCopy->getHighlighted());
        self::assertTrue($criterionVersionCopy->getRequired());
        self::assertTrue($criterionVersionCopy->getConfidential());
    }

    public function testFindReportVersionForProjectVersion(): void
    {
        $projectVersion = new Version();
        $projectVersion->setId(1);

        $evaluationReportVersion1 = new EvaluationReport\Version();
        $evaluationReportVersion1->setId(1);

        $evaluationReportVersionRepositoryMock = $this->getMockBuilder(VersionRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByProjectVersion'])
            ->getMock();

        $evaluationReportVersionRepositoryMock->expects(self::once())
            ->method('findByProjectVersion')
            ->with($projectVersion)
            ->willReturn($evaluationReportVersion1);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository'])
            ->getMock();

        $entityManagerMock->expects(self::once())
            ->method('getRepository')
            ->with(EvaluationReport\Version::class)
            ->willReturn($evaluationReportVersionRepositoryMock);

        /** @var EntityManager $entityManagerMock */
        $service = new EvaluationReportService($entityManagerMock);

        $evaluationReportVersion2 = $service->findReportVersionForProjectVersion($projectVersion);
        self::assertEquals($evaluationReportVersion1->getId(), $evaluationReportVersion2->getId());
    }

    public function testFindReportVersionForProjectReport(): void
    {
        $evaluationReportVersion1 = new EvaluationReport\Version();
        $evaluationReportVersion1->setId(1);

        $evaluationReportVersionRepositoryMock = $this->getMockBuilder(VersionRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findForProjectReport'])
            ->getMock();

        $evaluationReportVersionRepositoryMock->expects(self::once())
            ->method('findForProjectReport')
            ->willReturn($evaluationReportVersion1);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository'])
            ->getMock();

        $entityManagerMock->expects(self::once())
            ->method('getRepository')
            ->with(EvaluationReport\Version::class)
            ->willReturn($evaluationReportVersionRepositoryMock);

        /** @var EntityManager $entityManagerMock */
        $service = new EvaluationReportService($entityManagerMock);

        $evaluationReportVersion2 = $service->findReportVersionForProjectReport();
        self::assertEquals($evaluationReportVersion1->getId(), $evaluationReportVersion2->getId());
    }

    public function testTypeIsConfidential(): void
    {
        $evaluationReportVersion = new EvaluationReport\Version();
        $evaluationReportVersion->setId(1);

        $criterionType = new CriterionType();
        $criterionType->setId(1);

        $criterionVersionRepositoryMock = $this->getMockBuilder(CriterionVersionRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['count'])
            ->getMock();

        $criterionVersionRepositoryMock->expects(self::exactly(2))
            ->method('count')
            ->with([
                'type'          => $criterionType,
                'reportVersion' => $evaluationReportVersion,
                'confidential'  => true
            ])
            ->will(self::onConsecutiveCalls(0, 1));

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository'])
            ->getMock();

        $entityManagerMock->expects(self::exactly(2))
            ->method('getRepository')
            ->with(CriterionVersion::class)
            ->willReturn($criterionVersionRepositoryMock);

        /** @var EntityManager $entityManagerMock */
        $service = new EvaluationReportService($entityManagerMock);

        self::assertFalse($service->typeIsConfidential($criterionType, $evaluationReportVersion));
        self::assertTrue($service->typeIsConfidential($criterionType, $evaluationReportVersion));
    }

    public function testTypeIsDeletable(): void
    {
        $criterionType = new CriterionType();
        $criterionType->setId(1);

        $criterionVersionRepositoryMock = $this->getMockBuilder(CriterionVersionRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['count'])
            ->getMock();

        $criterionVersionRepositoryMock->expects(self::exactly(2))
            ->method('count')
            ->with(['type' => $criterionType])
            ->will(self::onConsecutiveCalls(0, 1));

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository'])
            ->getMock();

        $entityManagerMock->expects(self::exactly(2))
            ->method('getRepository')
            ->with(CriterionVersion::class)
            ->willReturn($criterionVersionRepositoryMock);

        /** @var EntityManager $entityManagerMock */
        $service = new EvaluationReportService($entityManagerMock);

        self::assertTrue($service->typeIsDeletable($criterionType));
        self::assertFalse($service->typeIsDeletable($criterionType));
    }
}
