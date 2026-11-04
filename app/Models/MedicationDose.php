<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'medication_schedule_id',
    'patient_id',
    'medicine_name',
    'dosage',
    'slot',
    'scheduled_date',
    'scheduled_time',
    'instructions',
    'status',
    'taken_at',
])]
class MedicationDose extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'taken_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<MedicationSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MedicationSchedule::class, 'medication_schedule_id');
    }

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /** @return HasOne<AdherenceRecord, $this> */
    public function adherenceRecord(): HasOne
    {
        return $this->hasOne(AdherenceRecord::class);
    }

    /** @return HasMany<TrackerNotificationLog, $this> */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(TrackerNotificationLog::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isTaken(): bool
    {
        return $this->status === 'taken';
    }

    public function isMissed(): bool
    {
        return $this->status === 'missed';
    }

    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }
}

