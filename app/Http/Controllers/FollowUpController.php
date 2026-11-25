<?php

namespace App\Http\Controllers;

use App\Models\CaseRecord;
use App\Models\FollowUp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    private function ensureAdmin(): void
    {
        // Follow-up notes are part of a private case history, so only guidance administrators may read or change them.
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    public function store(Request $request, CaseRecord $case): RedirectResponse
    {
        $this->ensureAdmin();

        // Validate note length and date before associating the entry with this case.
        $validated = $request->validate([
            'follow_up_date' => ['required', 'date'],
            'notes' => ['required', 'string', 'max:5000'],
            'action_taken' => ['nullable', 'string', 'max:255'],
        ]);

        // Create through the parent relationship so the route's case ID is saved as case_record_id.
        // Record the currently signed-in admin as the author for the case history.
        $case->followUps()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('cases.show', $case)->with('success', 'Follow-up recorded.');
    }

    public function update(Request $request, CaseRecord $case, FollowUp $followUp): RedirectResponse
    {
        $this->ensureAdmin();
        // A follow-up ID is independently route-bound; reject it unless it belongs to this case URL.
        abort_unless((int) $followUp->case_record_id === (int) $case->id, 404);

        // Keep the original author while allowing staff to correct the date, action, or note.
        $validated = $request->validate([
            'follow_up_date' => ['required', 'date'],
            'notes' => ['required', 'string', 'max:5000'],
            'action_taken' => ['nullable', 'string', 'max:255'],
        ]);

        $followUp->update($validated);

        return redirect()->route('cases.show', $case)->with('success', 'Follow-up updated.');
    }

}
