<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['first_name', 'last_name', 'email', 'password', 'phone', 'subject', 'role', 'status', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'must_change_password' => 'boolean',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim("{$this->last_name} {$this->first_name}"),
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    public function isGuardian(): bool
    {
        return $this->role === UserRole::Guardian;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Children belonging to this user, when the user is a guardian.
     *
     * @return HasMany<Child, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Child::class, 'guardian_id');
    }

    /**
     * Availability windows authored by this user, when the user is a teacher.
     *
     * @return HasMany<Availability, $this>
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class, 'teacher_id');
    }

    /**
     * @return HasMany<AppointmentSlot, $this>
     */
    public function appointmentSlots(): HasMany
    {
        return $this->hasMany(AppointmentSlot::class, 'teacher_id');
    }

    /**
     * Appointments where this user is the teacher.
     *
     * @return HasMany<Appointment, $this>
     */
    public function appointmentsAsTeacher(): HasMany
    {
        return $this->hasMany(Appointment::class, 'teacher_id');
    }

    /**
     * Appointments where this user is the guardian.
     *
     * @return HasMany<Appointment, $this>
     */
    public function appointmentsAsGuardian(): HasMany
    {
        return $this->hasMany(Appointment::class, 'guardian_id');
    }

    /**
     * This deliberately overrides the Notifiable trait's default
     * `notifications()` relation (Laravel's polymorphic database
     * notifications) with our own custom, spec-defined notifications table.
     * `notify()` (used for e.g. password reset emails via the mail channel)
     * still works normally — only the unused database-channel/relation is
     * replaced.
     *
     * @return HasMany<Notification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id')->latest('created_at');
    }
}
