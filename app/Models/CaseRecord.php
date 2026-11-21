<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseRecord extends Model
{
    // These case fields may be saved from the case controller after validation.
    protected $fillable = [
        'student_id',
        'created_by',
        'appointment_id',
        'case_number',
        'title',
        'category',
        'description',
        'remarks',
        'session_date',
        'session_time',
        'session_end_time',
        'findings',
        'recommendations',
        'status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function followUps(): HasMany
    {
        // Follow-ups are returned with the newest note first.
        return $this->hasMany(FollowUp::class)->orderByDesc('follow_up_date');
    }
}
