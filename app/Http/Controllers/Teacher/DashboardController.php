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
            ->with(['guardian', 'child', 'slot'])
            ->where('status', AppointmentStatus::New)
            ->whereHas('slot', fn ($q) => $q->where('date', today()->toDateString()))
            ->get()
            ->sortBy(fn ($appointment) => $appointment->slot->start_time)
            ->values();

        $recentCancellations = $teacher->appointmentsAsTeacher()
            ->with(['guardian', 'child', 'slot'])
            ->where('status', AppointmentStatus::Cancelled)
            ->orderByDesc('cancelled_at')
            ->limit(5)
            ->get();

        return view('teacher.dashboard', [
            'teacher' => $teacher,
            'todaysAppointments' => $todaysAppointments,
            'nextAppointment' => $todaysAppointments->first(fn ($a) => $a->slot->start_time >= now()->format('H:i:s')),
            'recentCancellations' => $recentCancellations,
        ]);
    }
}
