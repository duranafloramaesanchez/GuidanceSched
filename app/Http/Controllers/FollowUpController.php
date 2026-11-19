<?php

namespace App\Http\Controllers;

use App\Models\CaseRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    public function store(Request $request, CaseRecord $case): RedirectResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'follow_up_date' => ['required', 'date'],
            'notes' => ['required', 'string', 'max:5000'],
            'action_taken' => ['nullable', 'string', 'max:255'],
        ]);

        $case->followUps()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('cases.show', $case)->with('success', 'Follow-up recorded.');
    }

}
