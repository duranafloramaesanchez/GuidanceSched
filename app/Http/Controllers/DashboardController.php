<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\CaseRecord;
use App\Models\FollowUp;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Build dashboard totals from the same role-scoped appointment query used for each person.
     * Case and follow-up totals are added only for administrators because those records are private.
     */
    public function index(): View
    {
        $user = auth()->user();

        // Start with only the appointments this user is allowed to count.
        if ($user->role === 'admin') {
            $appointments = Appointment::query();
        } elseif ($user->role === 'teacher') {
            $appointments = Appointment::query()
                ->whereHas('student', fn ($students) => $students->where('assigned_teacher_id', $user->id));
        } else {
            // Students only get counts for requests that have an assigned time.
            $appointments = Appointment::query()
                ->where('student_id', $user->id)
                ->whereNotNull('scheduled_at');
        }

        // Clone the base query so each count adds its own status filter without affecting the next one.
        $stats = [
            'pending' => (clone $appointments)->where('status', 'pending')->count(),
            'approved' => (clone $appointments)->where('status', 'approved')->count(),
            'completed' => (clone $appointments)->where('status', 'completed')->count(),
            'cancelled' => (clone $appointments)->where('status', 'cancelled')->count(),
        ];

        // Guidance-only case and follow-up counts stay off teacher and student dashboards.
        if ($user->role === 'admin') {
            $stats['open_cases'] = CaseRecord::whereIn('status', ['open', 'in_progress'])->count();
            $stats['follow_ups_today'] = FollowUp::whereDate('follow_up_date', today())->count();
        }

        return view('dashboard.index', compact('stats', 'user'));
    }
}
