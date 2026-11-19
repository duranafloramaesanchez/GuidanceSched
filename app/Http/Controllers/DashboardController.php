<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\CaseRecord;
use App\Models\FollowUp;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

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

        $stats = [
            'pending' => (clone $appointments)->where('status', 'pending')->count(),
            'approved' => (clone $appointments)->where('status', 'approved')->count(),
            'completed' => (clone $appointments)->where('status', 'completed')->count(),
            'cancelled' => (clone $appointments)->where('status', 'cancelled')->count(),
        ];

        if ($user->role === 'admin') {
            $stats['open_cases'] = CaseRecord::whereIn('status', ['open', 'in_progress'])->count();
            $stats['follow_ups_today'] = FollowUp::whereDate('follow_up_date', today())->count();
        }

        return view('dashboard.index', compact('stats', 'user'));
    }
}
