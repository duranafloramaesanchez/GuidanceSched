<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Appointment;
use App\Models\CaseRecord;
use App\Models\FollowUp;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // These accounts and records are fictional and exist only for demonstrations.
        $admin = User::firstOrCreate(['email' => 'admin@guidancesched.test'], [
            'name' => 'Demo Guidance Admin', 'role' => 'admin', 'password' => 'ChangeMe123!',
        ]);
        $teacher = User::firstOrCreate(['email' => 'teacher@guidancesched.test'], [
            'name' => 'Demo Teacher', 'role' => 'teacher', 'password' => 'ChangeMe123!',
        ]);
        $student = User::firstOrCreate(['email' => 'student@guidancesched.test'], [
            'name' => 'Demo Student', 'role' => 'student', 'password' => 'ChangeMe123!',
        ]);

        $appointment = Appointment::firstOrCreate(
            ['student_id' => $student->id, 'title' => 'Study planning consultation'],
            ['created_by' => $admin->id, 'reason' => 'Discussing study routines (fictional demo data).', 'scheduled_at' => now()->addDays(2), 'status' => 'pending'],
        );

        $case = CaseRecord::firstOrCreate(
            ['case_number' => 'DEMO-CASE-001'],
            ['student_id' => $student->id, 'created_by' => $admin->id, 'appointment_id' => $appointment->id,
                'title' => 'Study support', 'category' => 'academic', 'description' => 'Synthetic demonstration case.', 'status' => 'open'],
        );

        FollowUp::firstOrCreate(
            ['case_record_id' => $case->id, 'follow_up_date' => today()->toDateString()],
            ['created_by' => $admin->id, 'notes' => 'Synthetic follow-up entry for demonstration.', 'action_taken' => 'Discussed study plan'],
        );
    }
}
