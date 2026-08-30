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
            ->join('appointment_slots', 'appointments.slot_id', '=', 'appointment_slots.id')
            ->with(['guardian', 'child', 'slot'])
            ->when($request->filled('status'), fn ($q) => $q->where('appointments.status', $request->string('status')))
            ->when($request->filled('date'), fn ($q) => $q->where('appointment_slots.date', $request->string('date')))
            ->orderBy('appointment_slots.date')
            ->orderBy('appointment_slots.start_time')
            ->select('appointments.*')
            ->get();

        return view('teacher.appointments.index', ['appointments' => $appointments]);
    }
}
