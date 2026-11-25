<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseRecord extends Model
{
    // Case fields allowed for mass assignment after the admin-only controller validates the request.
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
        // Student whose guidance history this private record describes.
        return $this->belongsTo(User::class, 'student_id');
    }

    public function creator(): BelongsTo
    {
        // Admin who opened the case; useful for identifying the original record owner.
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appointment(): BelongsTo
    {
        // Optional consultation linked to this case, if the case was opened from a request.
        return $this->belongsTo(Appointment::class);
    }

    public function followUps(): HasMany
    {
        // One case may have many dated notes; show the newest follow-up first on case detail pages.
        return $this->hasMany(FollowUp::class)->orderByDesc('follow_up_date');
    }
}
