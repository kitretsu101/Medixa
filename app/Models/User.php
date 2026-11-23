<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'hospital_id'])]
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
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────

    /** @return BelongsTo<Hospital, $this> */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

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

    /** @return HasMany<Appointment, $this> */
    public function doctorAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    /** @return HasMany<LabReport, $this> */
    public function assignedReports(): HasMany
    {
        return $this->hasMany(LabReport::class, 'doctor_id');
    }

    /** @return HasMany<MedicationSchedule, $this> */
    public function medicationSchedules(): HasMany
    {
        return $this->hasMany(MedicationSchedule::class, 'patient_id');
    }

    /** @return HasMany<RecoveryJourney, $this> */
    public function recoveryJourneys(): HasMany
    {
        return $this->hasMany(RecoveryJourney::class, 'patient_id');
    }

    /** @return HasMany<TrackerInvitation, $this> */
    public function trackerInvitations(): HasMany
    {
        return $this->hasMany(TrackerInvitation::class, 'patient_id');
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

    public function isLabAssistant(): bool
    {
        return $this->role === 'lab_assistant';
    }

    public function isReceptionist(): bool
    {
        return $this->role === 'receptionist';
    }
}
