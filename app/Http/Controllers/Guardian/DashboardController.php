<?php

namespace App\Http\Controllers\Guardian;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $children = $request->user()->children()->orderBy('first_name')->get();

        $upcomingAppointments = $request->user()->appointmentsAsGuardian()
            ->with(['teacher', 'child'])
            ->where('status', AppointmentStatus::New)
            ->where('date', '>=', today()->toDateString())
            ->orderBy('booked_at')
            ->limit(5)
            ->get();

        return view('guardian.dashboard', [
            'children' => $children,
            'upcomingAppointments' => $upcomingAppointments,
        ]);
    }
}
