<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    public function index(Request $request): View
    {
        Appointment::completePast();

        $appointments = $request->user()->appointmentsAsTeacher()
            ->with(['guardian', 'child'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('date', $request->string('date')->toString()))
            ->upcomingFirst()
            ->get();

        return view('teacher.appointments.index', ['appointments' => $appointments]);
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        // The policy also lets the guardian and admins through, but this
        // route is only for the teacher the appointment was booked with.
        abort_unless($appointment->teacher_id === $request->user()->id, 403);
        $this->authorize('cancel', $appointment);
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $reason = $request->string('reason')->trim()->toString() ?: null;

        try {
            $this->bookingService->cancel($appointment, $request->user(), $reason);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('teacher.appointments.index')->with('status', 'appointment-cancelled');
    }
}
