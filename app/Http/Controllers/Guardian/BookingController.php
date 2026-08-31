<?php

namespace App\Http\Controllers\Guardian;

use App\Enums\SlotStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\SlotUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\AppointmentSlot;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    public function teachers(): View
    {
        $teachers = User::where('role', UserRole::Teacher)
            ->where('status', UserStatus::Active)
            ->orderBy('last_name')
            ->get();

        $teacherIdsWithAvailability = AppointmentSlot::where('status', SlotStatus::Available)
            ->where('date', '>=', today()->toDateString())
            ->where(fn ($q) => $q->where('date', '>', today()->toDateString())
                ->orWhere('start_time', '>', now()->format('H:i:s')))
            ->whereIn('teacher_id', $teachers->pluck('id'))
            ->distinct()
            ->pluck('teacher_id')
            ->all();

        return view('guardian.booking.teachers', [
            'teachers' => $teachers,
            'teacherIdsWithAvailability' => $teacherIdsWithAvailability,
        ]);
    }

    public function pickDate(Request $request, User $teacher): View
    {
        abort_unless($teacher->isTeacher() && $teacher->isActive(), 404);

        // No calendar to page through any more — just the flat list of
        // dates the teacher has actually opened. Far simpler for a
        // guardian than hunting through a month grid for the handful of
        // days that matter.
        $availableDates = AppointmentSlot::where('teacher_id', $teacher->id)
            ->where('status', SlotStatus::Available)
            ->where('date', '>=', today()->toDateString())
            ->where(fn ($q) => $q->where('date', '>', today()->toDateString())
                ->orWhere('start_time', '>', now()->format('H:i:s')))
            ->distinct()
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($d) => $d instanceof \DateTimeInterface ? $d->format('Y-m-d') : (string) $d)
            ->all();

        $date = $request->string('date')->toString();

        // Default to the nearest available date; also the fallback for an
        // explicitly requested date that isn't actually open (stale link,
        // tampered URL) — there's no "empty date" state to show any more
        // since every listed date is guaranteed to have slots.
        if (! in_array($date, $availableDates, true)) {
            $date = $availableDates[0] ?? '';
        }

        $slots = null;

        if ($date) {
            $slots = AppointmentSlot::where('teacher_id', $teacher->id)
                ->where('date', $date)
                ->where('status', '!=', SlotStatus::Disabled)
                ->orderBy('start_time')
                ->get();
        }

        return view('guardian.booking.date', [
            'teacher' => $teacher,
            'date' => $date,
            'slots' => $slots,
            'availableDates' => $availableDates,
        ]);
    }

    public function confirm(Request $request, User $teacher, AppointmentSlot $slot): View
    {
        abort_unless($teacher->isTeacher() && $teacher->isActive(), 404);
        abort_unless($slot->teacher_id === $teacher->id, 404);
        abort_unless($slot->status === SlotStatus::Available, 404);

        $children = $request->user()->children()->orderBy('first_name')->get();

        return view('guardian.booking.confirm', [
            'teacher' => $teacher,
            'slot' => $slot,
            'children' => $children,
        ]);
    }

    public function store(Request $request, User $teacher, AppointmentSlot $slot): RedirectResponse
    {
        abort_unless($teacher->isTeacher() && $teacher->isActive(), 404);
        abort_unless($slot->teacher_id === $teacher->id, 404);

        $validated = $request->validate([
            'child_id' => ['required', 'integer'],
        ]);

        $child = $request->user()->children()->find($validated['child_id']);

        if (! $child) {
            throw ValidationException::withMessages(['child_id' => 'Παρακαλούμε επιλέξτε ένα από τα παιδιά σας.']);
        }

        try {
            $this->bookingService->book($slot, $request->user(), $child);
        } catch (SlotUnavailableException $e) {
            return redirect()
                ->route('guardian.book.date', ['teacher' => $teacher, 'date' => $slot->date->toDateString()])
                ->withErrors(['slot' => $e->getMessage()]);
        }

        return redirect()->route('guardian.dashboard')->with('status', 'appointment-booked');
    }
}
