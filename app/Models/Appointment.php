<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    // Fields the controller is allowed to save or update for an appointment.
    protected $fillable = [
        'student_id',
        'created_by',
        'assigned_to',
        'title',
        'reason',
        'scheduled_at',
        'duration_minutes',
        'status',
        'notes',
    ];

    // The appointment workflow uses only these four statuses.
    public const STATUSES = ['pending', 'approved', 'completed', 'cancelled'];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        // The student who will attend this consultation.
        return $this->belongsTo(User::class, 'student_id');
    }

    public function assignedPersonnel(): BelongsTo
    {
        // The guidance staff member assigned to handle the consultation.
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
