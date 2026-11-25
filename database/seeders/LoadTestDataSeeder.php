<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\CaseRecord;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use RuntimeException;

class LoadTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Avoid accidentally inserting a large synthetic dataset into a live database.
        if (app()->environment('production') && ! filter_var(env('LOAD_TEST_ALLOW_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN)) {
            throw new RuntimeException('Load-test seeding is disabled in production. Set LOAD_TEST_ALLOW_PRODUCTION=true only for a controlled test.');
        }

        // Counts can be increased in the local .env to match the scale of a planned load test.
        $teacherCount = $this->positiveCount('LOAD_TEST_TEACHERS', 10);
        $studentCount = $this->positiveCount('LOAD_TEST_STUDENTS', 250);
        $appointmentCount = $this->positiveCount('LOAD_TEST_APPOINTMENTS', 2000);
        $caseCount = $this->positiveCount('LOAD_TEST_CASES', 500);
        $followUpsPerCase = $this->positiveCount('LOAD_TEST_FOLLOW_UPS_PER_CASE', 2);

        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $password = Hash::make((string) env('LOAD_TEST_PASSWORD', 'LoadTestPass123!'));

        // Stable email keys let reruns update the same test accounts instead of creating duplicates.
        $teachers = collect(range(1, $teacherCount))->map(function (int $number) use ($password): User {
            return User::query()->updateOrCreate(
                ['email' => sprintf('loadtest.teacher.%04d@guidancesched.test', $number)],
                [
                    'name' => sprintf('Load Test Teacher %04d', $number),
                    'role' => 'teacher',
                    'password' => $password,
                ],
            );
        })->values();

        $students = collect(range(1, $studentCount))->map(function (int $number) use ($teachers, $password): User {
            $teacher = $teachers[($number - 1) % $teachers->count()];

            return User::query()->updateOrCreate(
                ['email' => sprintf('loadtest.student.%05d@guidancesched.test', $number)],
                [
                    'name' => sprintf('Load Test Student %05d', $number),
                    'role' => 'student',
                    'password' => $password,
                    'student_number' => sprintf('LOAD-%06d', $number),
                    'grade_level' => sprintf('Grade %d', (($number - 1) % 6) + 7),
                    'section' => sprintf('Synthetic Section %02d', (($number - 1) % 12) + 1),
                    'assigned_teacher_id' => $teacher->id,
                ],
            );
        })->values();

        // Distribute requests across the generated students and teachers, cycling through every status.
        $appointments = collect(range(1, $appointmentCount))->map(function (int $number) use ($admin, $students, $teachers): Appointment {
            $student = $students[($number - 1) % $students->count()];
            $teacher = $teachers[($number - 1) % $teachers->count()];
            $status = Appointment::STATUSES[($number - 1) % count(Appointment::STATUSES)];
            $scheduledAt = in_array($status, ['pending', 'approved'], true)
                ? now()->addDays(($number % 60) + 1)->setTime(8 + ($number % 8), ($number % 2) * 30)
                : now()->subDays(($number % 180) + 1)->setTime(8 + ($number % 8), ($number % 2) * 30);

            return Appointment::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'title' => sprintf('Load-test consultation %06d', $number),
                ],
                [
                    'created_by' => $teacher->id,
                    'assigned_to' => $admin->id,
                    'reason' => 'Synthetic load-test reason. No real person or case is represented.',
                    'scheduled_at' => $scheduledAt,
                    'duration_minutes' => 30,
                    'status' => $status,
                    'notes' => 'Synthetic load-test note.',
                ],
            );
        })->values();

        // Give generated cases links to generated students and appointments for realistic list/detail queries.
        $cases = collect(range(1, $caseCount))->map(function (int $number) use ($admin, $students, $appointments): CaseRecord {
            $studentIndex = ($number - 1) % $students->count();
            $appointment = $appointments[($number - 1) % $appointments->count()];

            return CaseRecord::query()->updateOrCreate(
                ['case_number' => sprintf('LOADTEST-%06d', $number)],
                [
                    'student_id' => $students[$studentIndex]->id,
                    'created_by' => $admin->id,
                    'appointment_id' => $appointment->id,
                    'title' => sprintf('Synthetic support case %06d', $number),
                    'description' => 'Synthetic load-test description. No real person or confidential information is represented.',
                    'status' => ['open', 'in_progress', 'closed'][($number - 1) % 3],
                    'category' => ['academic', 'behavioral', 'personal'][($number - 1) % 3],
                ],
            );
        })->values();

        // Anchor follow-up dates to the case creation date so rerunning the seeder keeps the same keys.
        foreach ($cases as $caseIndex => $case) {
            for ($followUpNumber = 1; $followUpNumber <= $followUpsPerCase; $followUpNumber++) {
                $date = Carbon::parse($case->created_at)->subDays($followUpNumber * 7 + ($caseIndex % 30))->toDateString();

                FollowUp::query()->updateOrCreate(
                    [
                        'case_record_id' => $case->id,
                        'follow_up_date' => $date,
                    ],
                    [
                        'created_by' => $case->created_by,
                        'notes' => 'Synthetic load-test follow-up. No real person or confidential information is represented.',
                        'action_taken' => 'Recorded synthetic follow-up',
                    ],
                );
            }
        }

        $this->command?->info(sprintf(
            'Synthetic load-test data ready: %d teachers, %d students, %d appointments, %d cases, and up to %d follow-ups.',
            $teachers->count(),
            $students->count(),
            $appointments->count(),
            $cases->count(),
            $cases->count() * $followUpsPerCase,
        ));
    }

    private function positiveCount(string $environmentVariable, int $default): int
    {
        // A zero/negative count could leave later records without teachers, students, or parent rows.
        $count = (int) env($environmentVariable, $default);

        if ($count < 1) {
            throw new InvalidArgumentException(sprintf('%s must be a positive integer.', $environmentVariable));
        }

        return $count;
    }
}
