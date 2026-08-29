<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGuardianRequest;
use App\Http\Requests\Admin\UpdateGuardianRequest;
use App\Models\User;
use App\Services\AccountProvisioningService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class GuardianController extends Controller
{
    public function __construct(private readonly AccountProvisioningService $provisioning) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $guardians = User::where('role', UserRole::Guardian)
            ->when($request->string('search')->trim()->toString(), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->withCount('children')
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.guardians.index', ['guardians' => $guardians]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.guardians.create');
    }

    public function store(StoreGuardianRequest $request): RedirectResponse
    {
        $temporaryPassword = $this->provisioning->generateTemporaryPassword();

        User::create([
            ...$request->validated(),
            'role' => UserRole::Guardian,
            'status' => UserStatus::Active,
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.guardians.index')
            ->with('status', 'guardian-created')
            ->with('temporaryPassword', $temporaryPassword);
    }

    public function edit(User $guardian): View
    {
        abort_unless($guardian->isGuardian(), 404);
        $this->authorize('update', $guardian);

        $guardian->load('children');

        return view('admin.guardians.edit', ['guardian' => $guardian]);
    }

    public function update(UpdateGuardianRequest $request, User $guardian): RedirectResponse
    {
        abort_unless($guardian->isGuardian(), 404);

        $guardian->update($request->validated());

        return redirect()->route('admin.guardians.index')->with('status', 'guardian-updated');
    }

    public function toggleStatus(User $guardian): RedirectResponse
    {
        abort_unless($guardian->isGuardian(), 404);
        $this->authorize('update', $guardian);

        $guardian->update([
            'status' => $guardian->status === UserStatus::Active ? UserStatus::Inactive : UserStatus::Active,
        ]);

        return redirect()->route('admin.guardians.index')->with('status', 'guardian-status-updated');
    }

    public function resetPassword(User $guardian): RedirectResponse
    {
        abort_unless($guardian->isGuardian(), 404);
        $this->authorize('update', $guardian);

        $temporaryPassword = $this->provisioning->generateTemporaryPassword();

        $guardian->forceFill([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ])->save();

        return redirect()->route('admin.guardians.index')
            ->with('status', 'guardian-password-reset')
            ->with('temporaryPassword', $temporaryPassword);
    }

    public function destroy(User $guardian): RedirectResponse
    {
        abort_unless($guardian->isGuardian(), 404);
        $this->authorize('delete', $guardian);

        try {
            $guardian->delete();
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            return redirect()->route('admin.guardians.index')->withErrors([
                'guardian' => 'Δεν είναι δυνατή η διαγραφή: ο κηδεμόνας έχει καταχωρημένα παιδιά ή ιστορικό ραντεβού. Χρησιμοποιήστε απενεργοποίηση αντ\' αυτού.',
            ]);
        }

        return redirect()->route('admin.guardians.index')->with('status', 'guardian-deleted');
    }
}
