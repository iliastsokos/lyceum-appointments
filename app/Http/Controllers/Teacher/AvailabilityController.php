<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreAvailabilityRequest;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Services\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    public function __construct(private readonly AvailabilityService $availabilityService) {}

    public function index(Request $request): View
    {
        $availabilities = $request->user()->availabilities()
            ->where('date', '>=', today()->toDateString())
            ->withCount([
                'slots as available_count' => fn ($q) => $q->where('status', SlotStatus::Available),
                'slots as booked_count' => fn ($q) => $q->where('status', SlotStatus::Booked),
                'slots as disabled_count' => fn ($q) => $q->where('status', SlotStatus::Disabled),
            ])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('teacher.availability.index', ['availabilities' => $availabilities]);
    }

    public function store(StoreAvailabilityRequest $request): RedirectResponse
    {
        $this->availabilityService->createAvailability(
            $request->user(),
            $request->validated('date'),
            $request->validated('start_time'),
            $request->validated('end_time'),
        );

        return redirect()->route('teacher.availability.index')->with('status', 'availability-created');
    }

    public function show(Availability $availability): View
    {
        $this->authorize('view', $availability);

        $slots = $availability->slots()->orderBy('start_time')->get();

        return view('teacher.availability.show', ['availability' => $availability, 'slots' => $slots]);
    }

    public function destroy(Availability $availability): RedirectResponse
    {
        $this->authorize('delete', $availability);

        $this->availabilityService->deleteAvailability($availability);

        return redirect()->route('teacher.availability.index')->with('status', 'availability-removed');
    }

    public function toggleSlot(AppointmentSlot $slot): RedirectResponse
    {
        $this->authorize('update', $slot);

        $this->availabilityService->toggleSlot($slot);

        return redirect()->route('teacher.availability.show', $slot->availability_id)->with('status', 'slot-updated');
    }
}
