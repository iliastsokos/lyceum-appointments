<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeacherRequest;
use App\Http\Requests\Admin\UpdateTeacherRequest;
use App\Models\User;
use App\Services\AccountProvisioningService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function __construct(private readonly AccountProvisioningService $provisioning) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $teachers = User::where('role', UserRole::Teacher)
            ->when($request->string('search')->trim()->toString(), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.teachers.index', ['teachers' => $teachers]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.teachers.create');
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $temporaryPassword = $this->provisioning->generateTemporaryPassword();

        User::create([
            ...$request->validated(),
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.teachers.index')
            ->with('status', 'teacher-created')
            ->with('temporaryPassword', $temporaryPassword);
    }

    public function edit(User $teacher): View
    {
        abort_unless($teacher->isTeacher(), 404);
        $this->authorize('update', $teacher);

        return view('admin.teachers.edit', ['teacher' => $teacher]);
    }

    public function update(UpdateTeacherRequest $request, User $teacher): RedirectResponse
    {
        abort_unless($teacher->isTeacher(), 404);

        $teacher->update($request->validated());

        return redirect()->route('admin.teachers.index')->with('status', 'teacher-updated');
    }

    public function toggleStatus(User $teacher): RedirectResponse
    {
        abort_unless($teacher->isTeacher(), 404);
        $this->authorize('update', $teacher);

        $teacher->update([
            'status' => $teacher->status === UserStatus::Active ? UserStatus::Inactive : UserStatus::Active,
        ]);

        return redirect()->route('admin.teachers.index')->with('status', 'teacher-status-updated');
    }

    public function resetPassword(User $teacher): RedirectResponse
    {
        abort_unless($teacher->isTeacher(), 404);
        $this->authorize('update', $teacher);

        $temporaryPassword = $this->provisioning->generateTemporaryPassword();

        $teacher->forceFill([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ])->save();

        return redirect()->route('admin.teachers.index')
            ->with('status', 'teacher-password-reset')
            ->with('temporaryPassword', $temporaryPassword);
    }

    public function destroy(User $teacher): RedirectResponse
    {
        abort_unless($teacher->isTeacher(), 404);
        $this->authorize('delete', $teacher);

        try {
            $teacher->delete();
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            return redirect()->route('admin.teachers.index')->withErrors([
                'teacher' => 'Δεν είναι δυνατή η διαγραφή: ο εκπαιδευτικός έχει ιστορικό διαθεσιμότητας ή ραντεβού. Χρησιμοποιήστε απενεργοποίηση αντ\' αυτού.',
            ]);
        }

        return redirect()->route('admin.teachers.index')->with('status', 'teacher-deleted');
    }
}
