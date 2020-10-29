<?php

declare(strict_types=1);

namespace EvaluationTest\Service;

use Calendar\Entity\Calendar;
use Calendar\Entity\Contact as CalendarContact;
use Calendar\Entity\ContactRole;
use Calendar\Entity\ContactStatus;
use Contact\Entity\Contact;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Evaluation\Entity\Reviewer;
use Evaluation\Repository\Reviewer\ContactRepository;
use Evaluation\Service\ReviewerService;
use InvalidArgumentException;
use Project\Entity\Calendar\Calendar as ProjectCalendar;
use Project\Entity\Project;
use Project\Entity\Report\Report;
use Project\Entity\Version\Type;
use Project\Entity\Version\Version;
use ReflectionClass;
use ReflectionException;
use Testing\Util\AbstractServiceTest;

class ReviewerServiceTest extends AbstractServiceTest
{
    /**
     * @throws ReflectionException
     */
    public function testParseReviewHandle()
    {
        $contact = new Contact();
        $contact->setId(1);
        $contact->setProjectReviewerContact((new Reviewer\Contact())->setHandle('XYZ'));

        $entityManagerMock = $this->getEntityManagerMock();
        $service = new ReviewerService($entityManagerMock);

        // make private method accessible
        $reflection = new ReflectionClass(get_class($service));
        $method = $reflection->getMethod('parseReviewHandle');
        $method->setAccessible(true);

        $this->assertEquals('XYZ', $method->invokeArgs($service, [$contact]));

        $this->expectException(InvalidArgumentException::class);
        $contact->setProjectReviewerContact(null);
        $method->invokeArgs($service, [$contact]);
    }

    public function testGetPreferredReviewers()
    {
        $contact = new Contact();
        $contact->setId(1);
        $contact->setProjectReviewerContact((new Reviewer\Contact())->setHandle('XYZ'));

        $reviewer = new Reviewer();
        $reviewer->setId(1);
        $reviewer->setType((new Reviewer\Type())->setType('pr'));
        $reviewer->setContact($contact);

        $project = new Project();
        $project->getReviewers()->add($reviewer);

        $entityManagerMock = $this->getEntityManagerMock();
        $service = new ReviewerService($entityManagerMock);

        $preferredReviewers = $service->getPreferredReviewers($project);
        $this->assertCount(1, $preferredReviewers);
        $this->assertEquals('XYZ', reset($preferredReviewers));
    }

    public function testGetIgnoredReviewers()
    {
        $project = new Project();
        $reviewerContact1 = (new Reviewer\Contact())->setId(1)->setHandle('ABC');
        $reviewerContact2 = (new Reviewer\Contact())->setId(2)->setHandle('XYZ');

        $reviewContactRepositoryMock = $this->getMockBuilder(ContactRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findIgnoredReviewers'])
            ->getMock();

        $repositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findBy'])
            ->getMock();

        $reviewContactRepositoryMock->expects($this->once())
            ->method('findIgnoredReviewers')
            ->with($project)
            ->willReturn([$reviewerContact1]);

        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository'])
            ->getMock();

        $map = [
            [Reviewer\Contact::class, $reviewContactRepositoryMock],
            [Reviewer\Type::class, $repositoryMock],
            [Reviewer::class, $repositoryMock],
        ];
        $entityManagerMock->expects($this->exactly(count($map)))
            ->method('getRepository')
            ->will($this->returnValueMap($map));

        $ignoredReviewerType = new Reviewer\Type();
        $repositoryMock->expects($this->once())
            ->method('find')
            ->with(Reviewer\Type::TYPE_IGNORED)
            ->willReturn($ignoredReviewerType);

        $contact = (new Contact())->setProjectReviewerContact($reviewerContact2);
        $reviewer = (new Reviewer())->setContact($contact);
        $repositoryMock->expects($this->once())
            ->method('findBy')
            ->with([
                'project' => $project,
                'type'    => $ignoredReviewerType,
            ])
            ->willReturn([$reviewer]);

        /** @var EntityManager $entityManagerMock */
        $service = new ReviewerService($entityManagerMock);

        $ignoredReviewers = $service->getIgnoredReviewers($project);
        $this->assertIsArray($ignoredReviewers);
        $this->assertEquals('ABC', reset($ignoredReviewers));
        $this->assertEquals('XYZ', end($ignoredReviewers));
    }

    public function testGetReviewHistory()
    {
        // project version
        $poContact = new Contact();
        $poContact->setProjectReviewerContact((new Reviewer\Contact())->setHandle('XYZ'));
        $poReviewer = new Reviewer();
        $poReviewer->setContact($poContact);
        $poVersion = new Version();
        $poVersion->setVersionType((new Type())->setId(Type::TYPE_PO)->setType('po'));
        $poVersion->setDateSubmitted(DateTime::createFromFormat('Y-m-d', '2019-08-01'));
        $poVersion->getReviewers()->add($poReviewer);

        // Future calendar
        $futureStgContact = new Contact();
        $futureStgContact->setProjectReviewerContact((new Reviewer\Contact())->setHandle('ABC'));
        $stgRole = new ContactRole();
        $stgRole->setId(ContactRole::ROLE_STG_REVIEWER);
        $futureCalendar = new Calendar();
        $futureDate = new DateTime();
        $futureDate->modify('+2 days');
        $futureCalendar->setDateFrom($futureDate);
        $futureCalendarContact = new CalendarContact();
        $futureCalendarContact->setContact($futureStgContact);
        $futureCalendarContact->setCalendar($futureCalendar);
        $futureCalendarContact->setRole($stgRole);
        $futureCalendar->getCalendarContact()->add($futureCalendarContact);
        $futureProjectCalendar = new ProjectCalendar();
        $futureProjectCalendar->setCalendar($futureCalendar);

        // Past calendar
        $pastStgContact = new Contact();
        $pastStgContact->setProjectReviewerContact((new Reviewer\Contact())->setHandle('DEF'));
        $pastCalendar = new Calendar();
        $pastDate = new DateTime();
        $pastDate->modify('-2 days');
        $pastCalendar->setDateFrom($pastDate);
        $pastCalendarContact = new CalendarContact();
        $pastCalendarContact->setContact($pastStgContact);
        $pastCalendarContact->setCalendar($pastCalendar);
        $pastCalendarContact->setRole($stgRole);
        $pastCalendarContact->setStatus((new ContactStatus())->setId(ContactStatus::STATUS_ACCEPT));
        $pastCalendar->getCalendarContact()->add($pastCalendarContact);
        $pastProjectCalendar = new ProjectCalendar();
        $pastProjectCalendar->setCalendar($pastCalendar);

        // Project report
        $reportContact = new Contact();
        $reportContact->setProjectReviewerContact((new Reviewer\Contact())->setHandle('GHI'));
        $reportReviewer = new Reviewer();
        $reportReviewer->setContact($reportContact);
        $report = new Report();
        $report->setDateCreated(new DateTime());
        $report->getReviewers()->add($reportReviewer);

        // Project
        $project = new Project();
        $project->getVersion()->add($poVersion);
        $project->getProjectCalendar()->add($futureProjectCalendar);
        $project->getProjectCalendar()->add($pastProjectCalendar);
        $project->getReport()->add($report);
        
        $entityManagerMock = $this->getEntityManagerMock();
        $service = new ReviewerService($entityManagerMock);

        $reviewHistory = $service->getReviewHistory($project);

        $this->assertCount(4, $reviewHistory);
        $poData = array_shift($reviewHistory);
        $this->assertIsArray($poData[ReviewerService::TYPE_PO]);
        $this->assertEquals('XYZ', reset($poData[ReviewerService::TYPE_PO]));
        $feData = array_shift($reviewHistory);
        $this->assertIsArray($feData[ReviewerService::TYPE_R]);
        $this->assertEquals('DEF', reset($feData[ReviewerService::TYPE_R]));
        $rData = array_shift($reviewHistory);
        $this->assertIsArray($rData[ReviewerService::TYPE_PPR]);
        $this->assertEquals('GHI', reset($rData[ReviewerService::TYPE_PPR]));
        $pprData = array_shift($reviewHistory);
        $this->assertIsArray($pprData[ReviewerService::TYPE_FE]);
        $this->assertEquals('ABC', reset($pprData[ReviewerService::TYPE_FE]));
    }
}
