<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\CaseRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuidanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_only_their_own_appointments(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        Appointment::create(['student_id' => $student->id, 'title' => 'My request', 'scheduled_at' => now()->addDays(2), 'status' => 'pending']);
        Appointment::create(['student_id' => $otherStudent->id, 'title' => 'Private request', 'scheduled_at' => now()->addDays(2), 'status' => 'pending']);

        $this->actingAs($student)->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('My request')
            ->assertDontSee('Private request');
    }

    public function test_student_cannot_open_staff_case_records(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('cases.index'))->assertForbidden();
    }

    public function test_valid_consultation_request_is_saved_for_signed_in_user(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)->post(route('appointments.store'), [
            'title' => 'Planning meeting',
            'reason' => 'Synthetic test reason',
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('appointments.index'));

        $this->assertDatabaseHas('appointments', [
            'student_id' => $teacher->id,
            'title' => 'Planning meeting',
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_cancel_another_students_appointment(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $appointment = Appointment::create(['student_id' => $otherStudent->id, 'title' => 'Other request', 'scheduled_at' => now()->addDays(2), 'status' => 'pending']);

        $this->actingAs($student)
            ->patch(route('appointments.cancel', $appointment))
            ->assertForbidden();
    }

    public function test_admin_can_record_a_follow_up_for_a_case(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $case = CaseRecord::create([
            'student_id' => $student->id,
            'created_by' => $admin->id,
            'case_number' => 'CASE-TEST-001',
            'title' => 'Synthetic case',
            'category' => 'academic',
            'description' => 'Synthetic test content',
            'status' => 'open',
        ]);

        $this->actingAs($admin)->post(route('cases.follow-ups.store', $case), [
            'follow_up_date' => today()->toDateString(),
            'notes' => 'Synthetic follow-up note',
            'action_taken' => 'Study plan discussed',
        ])->assertRedirect(route('cases.show', $case));

        $this->assertDatabaseHas('follow_ups', [
            'case_record_id' => $case->id,
            'created_by' => $admin->id,
            'notes' => 'Synthetic follow-up note',
        ]);
    }

    public function test_api_basic_auth_returns_only_the_signed_in_students_requests(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => 'demo-password']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        Appointment::create(['student_id' => $student->id, 'title' => 'API own request', 'scheduled_at' => now()->addDays(2), 'status' => 'pending', 'notes' => 'Private staff note']);
        Appointment::create(['student_id' => $otherStudent->id, 'title' => 'API private request', 'scheduled_at' => now()->addDays(2), 'status' => 'pending']);

        $this->withBasicAuth($student->email, 'demo-password')
            ->getJson('/api/appointments')
            ->assertOk()
            ->assertJsonFragment(['title' => 'API own request'])
            ->assertJsonMissing(['title' => 'API private request'])
            ->assertJsonMissingPath('data.0.notes');
    }

    public function test_student_cannot_read_confidential_cases_through_api(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => 'demo-password']);

        $this->withBasicAuth($student->email, 'demo-password')
            ->getJson('/api/cases')
            ->assertForbidden();
    }

    public function test_documented_api_workflows_for_appointments_cases_and_follow_ups(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => 'demo-password']);
        $admin = User::factory()->create(['role' => 'admin', 'password' => 'demo-password']);

        $createdAppointment = $this->withBasicAuth($student->email, 'demo-password')
            ->postJson('/api/appointments', [
                'title' => 'API consultation',
                'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'reason' => 'Synthetic reason',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->json('data.id');

        $this->withBasicAuth($student->email, 'demo-password')
            ->getJson("/api/appointments/{$createdAppointment}")
            ->assertOk()
            ->assertJsonPath('data.title', 'API consultation');

        $this->withBasicAuth($student->email, 'demo-password')
            ->patchJson("/api/appointments/{$createdAppointment}", ['title' => 'Updated consultation'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated consultation');

        $this->withBasicAuth($student->email, 'demo-password')
            ->patchJson("/api/appointments/{$createdAppointment}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $case = CaseRecord::create([
            'student_id' => $student->id,
            'created_by' => $admin->id,
            'case_number' => 'CASE-API-001',
            'title' => 'Synthetic API case',
            'category' => 'academic',
            'description' => 'Synthetic confidential data',
            'status' => 'open',
        ]);

        $this->withBasicAuth($admin->email, 'demo-password')
            ->getJson('/api/cases')
            ->assertOk()
            ->assertJsonFragment(['case_number' => 'CASE-API-001']);

        $this->withBasicAuth($admin->email, 'demo-password')
            ->getJson("/api/cases/{$case->id}")
            ->assertOk()
            ->assertJsonPath('data.description', 'Synthetic confidential data');

        $this->withBasicAuth($admin->email, 'demo-password')
            ->patchJson("/api/cases/{$case->id}/status", ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->withBasicAuth($admin->email, 'demo-password')
            ->getJson("/api/cases/{$case->id}/follow-ups")
            ->assertOk()
            ->assertJsonPath('data', []);

        $this->withBasicAuth($admin->email, 'demo-password')
            ->postJson("/api/cases/{$case->id}/follow-ups", [
                'follow_up_date' => today()->toDateString(),
                'notes' => 'Synthetic API follow-up',
                'action_taken' => 'Checked in',
            ])
            ->assertCreated()
            ->assertJsonPath('data.notes', 'Synthetic API follow-up');
    }
}
