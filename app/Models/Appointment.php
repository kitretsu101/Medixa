<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['patient_id', 'doctor_id', 'hospital_id', 'reason', 'appointment_date', 'serial_number', 'status', 'priority_type', 'visit_type', 'checked_in_at', 'consultation_started_at', 'consultation_ended_at', 'last_queue_notice'])]
class Appointment extends Model
{
    use HasFactory;

    /** @var list<string> */
    public const ACTIVE_STATUSES = ['pending', 'confirmed', 'checked_in', 'waiting', 'in_progress', 'approved'];

    /** @return BelongsTo<Hospital, $this> */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'checked_in_at' => 'datetime',
            'consultation_started_at' => 'datetime',
            'consultation_ended_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────

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

    /** @return HasOne<Prescription, $this> */
    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }

    /** @return HasOne<AppointmentFeedback, $this> */
    public function feedback(): HasOne
    {
        return $this->hasOne(AppointmentFeedback::class);
    }
}
