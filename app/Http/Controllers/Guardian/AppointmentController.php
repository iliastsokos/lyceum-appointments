<?php

namespace App\Http\Controllers\Guardian;

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
        $appointments = $request->user()->appointmentsAsGuardian()
            ->with(['teacher', 'child'])
            ->when($request->filled('teacher_id'), fn ($q) => $q->where('teacher_id', $request->integer('teacher_id')))
            ->when($request->filled('child_id'), fn ($q) => $q->where('child_id', $request->integer('child_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('guardian.appointments.index', [
            'appointments' => $appointments,
            'teachers' => $request->user()->appointmentsAsGuardian()->with('teacher')->get()->pluck('teacher')->unique('id'),
            'children' => $request->user()->children,
        ]);
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('cancel', $appointment);

        $reason = $request->string('reason')->toString() ?: null;

        try {
            $this->bookingService->cancel($appointment, $request->user(), $reason);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('guardian.appointments.index')->with('status', 'appointment-cancelled');
    }
}
