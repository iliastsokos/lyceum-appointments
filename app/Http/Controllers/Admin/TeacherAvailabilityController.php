<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeacherAvailabilityRequest;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeacherAvailabilityController extends Controller
{
    public function __construct(private readonly AvailabilityService $availabilityService) {}

    public function index(User $teacher): View
    {
        abort_unless($teacher->isTeacher(), 404);
        $this->authorize('view', $teacher);

        $availabilities = $teacher->availabilities()
            ->where('date', '>=', today()->toDateString())
            ->withCount([
                'slots as available_count' => fn ($q) => $q->where('status', SlotStatus::Available),
                'slots as booked_count' => fn ($q) => $q->where('status', SlotStatus::Booked),
                'slots as disabled_count' => fn ($q) => $q->where('status', SlotStatus::Disabled),
            ])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('admin.teachers.availability.index', ['teacher' => $teacher, 'availabilities' => $availabilities]);
    }

    public function store(StoreTeacherAvailabilityRequest $request, User $teacher): RedirectResponse
    {
        abort_unless($teacher->isTeacher(), 404);
        $this->authorize('view', $teacher);

        $this->availabilityService->createAvailability(
            $teacher,
            $request->validated('date'),
            $request->validated('start_time'),
            $request->validated('end_time'),
        );

        return redirect()->route('admin.teachers.availability.index', $teacher)->with('status', 'availability-created');
    }

    public function show(User $teacher, Availability $availability): View
    {
        abort_unless($teacher->isTeacher(), 404);
        abort_unless($availability->teacher_id === $teacher->id, 404);
        $this->authorize('view', $availability);

        $slots = $availability->slots()->orderBy('start_time')->get();

        return view('admin.teachers.availability.show', ['teacher' => $teacher, 'availability' => $availability, 'slots' => $slots]);
    }

    public function destroy(User $teacher, Availability $availability): RedirectResponse
    {
        abort_unless($teacher->isTeacher(), 404);
        abort_unless($availability->teacher_id === $teacher->id, 404);
        $this->authorize('delete', $availability);

        $this->availabilityService->deleteAvailability($availability);

        return redirect()->route('admin.teachers.availability.index', $teacher)->with('status', 'availability-removed');
    }

    public function toggleSlot(User $teacher, AppointmentSlot $slot): RedirectResponse
    {
        abort_unless($teacher->isTeacher(), 404);
        abort_unless($slot->teacher_id === $teacher->id, 404);
        $this->authorize('update', $slot);

        $this->availabilityService->toggleSlot($slot);

        return redirect()->route('admin.teachers.availability.show', [$teacher, $slot->availability_id])->with('status', 'slot-updated');
    }
}
