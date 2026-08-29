<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $appointments = $request->user()->appointmentsAsTeacher()
            ->with(['guardian', 'child', 'slot'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('date'), fn ($q) => $q->whereHas(
                'slot', fn ($sub) => $sub->where('date', $request->string('date'))
            ))
            ->orderByDesc('booked_at')
            ->get();

        return view('teacher.appointments.index', ['appointments' => $appointments]);
    }
}
