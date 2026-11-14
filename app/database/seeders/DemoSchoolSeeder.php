<?php

namespace Database\Seeders;

use App\Models\User;
use Classivo\Core\Models\AcademicSession;
use Classivo\Core\Models\Branch;
use Classivo\Core\Models\ClassSection;
use Classivo\Core\Models\ClassSubject;
use Classivo\Core\Models\School;
use Classivo\Core\Models\SchoolClass;
use Classivo\Core\Models\Section;
use Classivo\Core\Models\Subject;
use Classivo\Core\Tenancy\CurrentContext;
use Classivo\Students\Models\Student;
use Classivo\Students\Models\StudentCategory;
use Classivo\Students\Services\AttendanceService;
use Classivo\Students\Services\StudentAdmissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * A fictional demo school. Every name, phone and email here is made up.
 *
 * Logins (password "password"): admin@example.com (admin panel),
 * viewer@example.com (admin panel, read-only),
 * student@example.com (student portal, linked to the first student),
 * parent@example.com (parent portal, that student's primary guardian).
 */
class DemoSchoolSeeder extends Seeder
{
    private const FIRST_NAMES = [
        ['Aarav', 'male'], ['Diya', 'female'], ['Kabir', 'male'], ['Meera', 'female'], ['Vivaan', 'male'],
        ['Ananya', 'female'], ['Ishaan', 'male'], ['Saanvi', 'female'], ['Arjun', 'male'], ['Kavya', 'female'],
        ['Reyansh', 'male'], ['Myra', 'female'], ['Vihaan', 'male'], ['Anika', 'female'], ['Dhruv', 'male'],
    ];

    private const LAST_NAMES = ['Sharma', 'Verma', 'Iyer', 'Nair', 'Khan', 'Singh', 'Das', 'Patel', 'Joshi', 'Menon'];

    private const PARENT_NAMES = ['Suresh', 'Mahesh', 'Vikram', 'Anil', 'Deepak', 'Sanjay', 'Naveen', 'Ravi'];

    public function run(): void
    {
        // Demo accounts use the well-known password "password". Refuse to create them
        // on a production install unless explicitly allowed (the docker demo does so).
        if (app()->isProduction() && ! filter_var(env('CLASSIVO_ALLOW_DEMO_SEED', false), FILTER_VALIDATE_BOOLEAN)) {
            throw new \RuntimeException('DemoSchoolSeeder refuses to run with APP_ENV=production. Set CLASSIVO_ALLOW_DEMO_SEED=true to seed demo data anyway.');
        }

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Demo Admin', 'password' => 'password', 'email_verified_at' => now()],
        );

        User::query()->updateOrCreate(
            ['email' => 'viewer@example.com'],
            ['name' => 'Demo Viewer', 'password' => 'password', 'email_verified_at' => now()],
        );

        $teacher = User::query()->updateOrCreate(
            ['email' => 'teacher@example.com'],
            ['name' => 'Demo Teacher', 'password' => 'password', 'email_verified_at' => now()],
        );

        $school = School::query()->firstOrCreate(['code' => 'DEMO'], [
            'name' => 'Demo Public School',
            'email' => 'office@example.com',
            'phone' => '+91 90000 00000',
            'city' => 'Demo City',
            'country' => 'IN',
            'board' => 'CBSE',
            'status' => true,
        ]);

        config(['classivo.school_id' => $school->id]);

        $branch = Branch::query()->firstOrCreate(['school_id' => $school->id, 'code' => 'MAIN'], [
            'name' => 'Main Campus',
            'city' => 'Demo City',
            'principal_name' => 'Demo Principal',
            'reg_no_prefix' => 'ADM',
            'reg_no_start' => 1,
            'reg_no_digits' => 4,
            'is_default' => true,
            'status' => true,
        ]);

        // Academic year April – March (India preset).
        $startYear = now()->month >= 4 ? now()->year : now()->year - 1;
        $session = AcademicSession::query()->firstOrCreate(['school_id' => $school->id, 'name' => $startYear.'-'.($startYear + 1)], [
            'start_date' => Carbon::create($startYear, 4, 1),
            'end_date' => Carbon::create($startYear + 1, 3, 31),
            'is_current' => true,
            'status' => true,
        ]);

        AcademicSession::query()->firstOrCreate(['school_id' => $school->id, 'name' => ($startYear + 1).'-'.($startYear + 2)], [
            'start_date' => Carbon::create($startYear + 1, 4, 1),
            'end_date' => Carbon::create($startYear + 2, 3, 31),
            'is_current' => false,
            'status' => true,
        ]);

        app(CurrentContext::class)->setSchoolId($school->id)->setBranchId($branch->id)->setSessionId($session->id);

        if (SchoolClass::query()->exists()) {
            return; // already seeded
        }

        $sections = collect(['A', 'B'])->map(fn (string $name) => Section::create(['name' => $name, 'capacity' => 40]));
        $subjects = collect([
            ['English', 'ENG'], ['Hindi', 'HIN'], ['Mathematics', 'MAT'], ['Environmental Studies', 'EVS'], ['Computer Science', 'CS'],
        ])->map(fn (array $s) => Subject::create(['name' => $s[0], 'code' => $s[1], 'type' => 'theory']));

        foreach (['General', 'RTE', 'Staff ward'] as $category) {
            StudentCategory::create(['name' => $category]);
        }

        foreach (range(1, 5) as $grade) {
            $class = SchoolClass::create(['name' => 'Grade '.$grade, 'numeric_order' => $grade]);
            $class->syncSections($sections->pluck('id')->all());

            ClassSubject::allot($class, null, $subjects->map(fn (Subject $s, int $i): array => [
                'subject_id' => $s->id,
                'teacher_id' => $teacher->id,
                'full_marks' => 100,
                'pass_marks' => 33,
                'sort_order' => $i,
            ])->all());
        }

        $this->admitStudents($sections);
        $this->linkStudentLogin();
        $this->takeAttendance();
    }

    private function admitStudents($sections): void
    {
        $admission = app(StudentAdmissionService::class);
        $categoryId = StudentCategory::query()->where('name', 'General')->value('id');
        $i = 0;

        foreach (SchoolClass::query()->orderBy('numeric_order')->get() as $class) {
            foreach ($sections as $section) {
                foreach (range(1, 3) as $n) {
                    [$first, $gender] = self::FIRST_NAMES[$i % count(self::FIRST_NAMES)];
                    $last = self::LAST_NAMES[$i % count(self::LAST_NAMES)];
                    $parent = self::PARENT_NAMES[$i % count(self::PARENT_NAMES)].' '.$last;
                    $phone = '90000'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT);

                    $admission->admit([
                        'class_id' => $class->id,
                        'section_id' => $section->id,
                        'student_category_id' => $categoryId,
                        'first_name' => $first,
                        'last_name' => $last,
                        'gender' => $gender,
                        'date_of_birth' => now()->subYears(5 + $class->numeric_order)->subDays($i * 11)->toDateString(),
                        'email' => strtolower($first.'.'.$last.$i).'@example.com',
                        'city' => 'Demo City',
                        'social_category' => 'general',
                        'guardian' => ['relation' => 'father', 'name' => $parent, 'phone' => $phone],
                    ]);

                    $i++;
                }
            }
        }
    }

    /** Demo student portal login (student@example.com / password) for the first student. */
    private function linkStudentLogin(): void
    {
        $student = Student::query()->orderBy('id')->first();

        if (! $student) {
            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => 'student@example.com'],
            ['name' => $student->full_name, 'password' => 'password', 'email_verified_at' => now()],
        );

        $student->forceFill(['user_id' => $user->id])->save();

        // Parent portal login for the same child's primary guardian.
        if ($guardian = $student->primaryGuardian()) {
            $parent = User::query()->updateOrCreate(
                ['email' => 'parent@example.com'],
                ['name' => $guardian->name, 'password' => 'password', 'email_verified_at' => now()],
            );

            $guardian->forceFill(['user_id' => $parent->id])->save();
        }
    }

    private function takeAttendance(): void
    {
        $service = app(AttendanceService::class);
        $statuses = ['present', 'present', 'present', 'present', 'present', 'present', 'late', 'absent', 'half_day', 'present'];
        mt_srand(2026);

        foreach (ClassSection::query()->get() as $classSection) {
            $roster = $service->roster($classSection);

            for ($d = 10; $d >= 1; $d--) {
                $date = now()->subDays($d);

                if ($service->isWeekend($classSection->branch_id, $date->toDateString())) {
                    continue;
                }

                $service->saveDaily($classSection, $date->toDateString(), $roster->map(fn ($s): array => [
                    'student_id' => $s->id,
                    'status' => $statuses[mt_rand(0, count($statuses) - 1)],
                ])->all(), notify: false);
            }
        }
    }
}
