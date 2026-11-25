<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    // Mass-assignable fields used after controllers validate a create or update request.
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

    // Allowed appointment states; controllers validate transitions and require a time before approval.
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
        // Optional administrator responsible for handling this consultation.
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        // Staff account that originally submitted the request; retained for audit context.
        return $this->belongsTo(User::class, 'created_by');
    }
}
