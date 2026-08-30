<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user();

        $todaysAppointments = $teacher->appointmentsAsTeacher()
            ->with(['guardian', 'child'])
            ->where('status', AppointmentStatus::New)
            ->where('date', today()->toDateString())
            ->orderBy('start_time')
            ->get();

        $recentCancellations = $teacher->appointmentsAsTeacher()
            ->with(['guardian', 'child'])
            ->where('status', AppointmentStatus::Cancelled)
            ->orderByDesc('cancelled_at')
            ->limit(5)
            ->get();

        return view('teacher.dashboard', [
            'teacher' => $teacher,
            'todaysAppointments' => $todaysAppointments,
            'nextAppointment' => $todaysAppointments->first(fn ($a) => $a->start_time >= now()->format('H:i:s')),
            'recentCancellations' => $recentCancellations,
        ]);
    }
}
