<?php

namespace App\Tests\Unit\Twig\Components;

use App\Entity\Cohort;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\CohortRepository;
use App\Repository\CourseRepository;
use App\Twig\Components\CourseCatalog;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class CourseCatalogTest extends TestCase
{
    private CourseRepository&MockObject $courseRepository;
    private CohortRepository&MockObject $cohortRepository;
    private Security&MockObject $security;
    private CourseCatalog $component;

    protected function setUp(): void
    {
        $this->courseRepository = $this->createMock(CourseRepository::class);
        $this->cohortRepository = $this->createMock(CohortRepository::class);
        $this->security = $this->createMock(Security::class);

        $this->component = new CourseCatalog(
            $this->courseRepository,
            $this->cohortRepository,
            $this->security
        );
    }

    public function testGetCoursesForAdminWithNoCohortId(): void
    {
        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $this->security->expects($this->never())
            ->method('getUser');

        $expectedCourses = [$this->createMock(Course::class)];

        $this->courseRepository->expects($this->once())
            ->method('findCatalogCourses')
            ->with([], true, '')
            ->willReturn($expectedCourses);

        $this->assertSame($expectedCourses, $this->component->getCourses());
    }

    public function testGetCoursesWithSpecificCohortId(): void
    {
        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $this->component->cohortId = 123;

        $cohort = $this->createMock(Cohort::class);
        $cohort->method('getId')->willReturn(123);

        $this->cohortRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($cohort);

        $expectedCourses = [$this->createMock(Course::class)];

        $this->courseRepository->expects($this->once())
            ->method('findCatalogCourses')
            ->with([$cohort], false, '')
            ->willReturn($expectedCourses);

        $this->assertSame($expectedCourses, $this->component->getCourses());
    }

    public function testGetCoursesWithInvalidCohortId(): void
    {
        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $this->component->cohortId = 999;

        $this->cohortRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $expectedCourses = [$this->createMock(Course::class)];

        $this->courseRepository->expects($this->once())
            ->method('findCatalogCourses')
            ->with([], false, '')
            ->willReturn($expectedCourses);

        $this->assertSame($expectedCourses, $this->component->getCourses());
    }

    public function testGetCoursesForRegularUserWithNoCohortId(): void
    {
        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $user = $this->createMock(User::class);
        $cohort = $this->createMock(Cohort::class);

        $this->security->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($user);

        $user->expects($this->once())
            ->method('getCohorts')
            ->willReturn(new ArrayCollection([$cohort]));

        $expectedCourses = [$this->createMock(Course::class)];

        $this->courseRepository->expects($this->once())
            ->method('findCatalogCourses')
            ->with([$cohort], false, '')
            ->willReturn($expectedCourses);

        $this->assertSame($expectedCourses, $this->component->getCourses());
    }

    public function testGetCoursesForAnonymousUserWithNoCohortId(): void
    {
        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $expectedCourses = [$this->createMock(Course::class)];

        $this->courseRepository->expects($this->once())
            ->method('findCatalogCourses')
            ->with([], false, '')
            ->willReturn($expectedCourses);

        $this->assertSame($expectedCourses, $this->component->getCourses());
    }

    public function testGetCoursesWithQuery(): void
    {
        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $this->component->query = 'search term';

        $expectedCourses = [$this->createMock(Course::class)];

        $this->courseRepository->expects($this->once())
            ->method('findCatalogCourses')
            ->with([], true, 'search term')
            ->willReturn($expectedCourses);

        $this->assertSame($expectedCourses, $this->component->getCourses());
    }
}
