<?php

namespace App\Models;

use App\Models\Appointment;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'password', 'role', 'assigned_teacher_id', 'student_number', 'grade_level', 'section',
    'contact_number', 'address', 'guardian_name', 'guardian_relationship', 'guardian_contact_number',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        // Laravel hashes a password before it is saved to the database.
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function appointmentsAsStudent(): HasMany
    {
        // Appointments where this user is the student receiving guidance.
        return $this->hasMany(Appointment::class, 'student_id');
    }

    public function assignedTeacher(): BelongsTo
    {
        return $this->belongsTo(self::class, 'assigned_teacher_id');
    }

    public function assignedStudents(): HasMany
    {
        // Students linked to this teacher account.
        return $this->hasMany(self::class, 'assigned_teacher_id')->where('role', 'student');
    }

    public function appointmentsAssigned(): HasMany
    {
        return $this->hasMany(Appointment::class, 'assigned_to');
    }

    public function createdCases(): HasMany
    {
        return $this->hasMany(CaseRecord::class, 'created_by');
    }
}
