<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['appointment_id', 'patient_id', 'doctor_id', 'diagnosis', 'medicines', 'instructions'])]
class Prescription extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'medicines' => 'array',
            'instructions' => 'encrypted',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /** @return BelongsTo<User, $this> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /** @return HasOne<MedicationSchedule, $this> */
    public function medicationSchedule(): HasOne
    {
        return $this->hasOne(MedicationSchedule::class);
    }

    /** @return HasOne<RecoveryJourney, $this> */
    public function recoveryJourney(): HasOne
    {
        return $this->hasOne(RecoveryJourney::class);
    }

    /** @return HasOne<TrackerInvitation, $this> */
    public function trackerInvitation(): HasOne
    {
        return $this->hasOne(TrackerInvitation::class);
    }
}
