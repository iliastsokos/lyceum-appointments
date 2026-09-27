<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
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

        $appointments = Appointment::query()
            ->with(['teacher', 'guardian', 'child'])
            ->when($request->filled('teacher_id'), fn ($q) => $q->where('teacher_id', $request->integer('teacher_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('date'), fn ($q) => $q->where('date', $request->string('date')))
            ->upcomingFirst()
            ->paginate(50)
            ->withQueryString();

        return view('admin.appointments.index', [
            'appointments' => $appointments,
            'teachers' => User::where('role', UserRole::Teacher)->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    /**
     * Cancel an appointment on the teacher's behalf (e.g. the teacher is
     * absent and can't do it themselves). Both the guardian and the teacher
     * are notified.
     */
    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('cancel', $appointment);
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $reason = $request->string('reason')->trim()->toString() ?: null;

        try {
            $this->bookingService->cancel($appointment, $request->user(), $reason);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', 'appointment-cancelled');
    }
}
