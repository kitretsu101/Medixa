<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
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
            'nid' => 'encrypted',
            'phone' => 'encrypted',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────

    public function patientProfile(): HasOne
    {
        return $this->hasOne(PatientProfile::class);
    }

    public function doctorProfile(): HasOne
    {
        return $this->hasOne(DoctorProfile::class);
    }

    public function liveQueue(): HasOne
    {
        return $this->hasOne(LiveQueue::class, 'doctor_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    /** @param Builder<User> $query */
    public function scopePatients(Builder $query): Builder
    {
        return $query->where('role', 'patient');
    }

    /** @param Builder<User> $query */
    public function scopeDoctors(Builder $query): Builder
    {
        return $query->where('role', 'doctor');
    }

    /** @param Builder<User> $query */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', 'admin');
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    public function isPatient(): bool
    {
        return $this->role === 'patient';
    }

    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
