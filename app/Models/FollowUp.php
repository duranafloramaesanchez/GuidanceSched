<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    // Editable note fields for a follow-up; the parent case and author are assigned by the controller.
    protected $fillable = [
        'case_record_id',
        'created_by',
        'follow_up_date',
        'notes',
        'action_taken',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date',
        ];
    }

    public function caseRecord(): BelongsTo
    {
        // Parent case whose history includes this entry.
        return $this->belongsTo(CaseRecord::class);
    }

    public function creator(): BelongsTo
    {
        // Admin who recorded the note, shown to authorized staff in the case history.
        return $this->belongsTo(User::class, 'created_by');
    }
}
