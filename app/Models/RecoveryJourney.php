<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'patient_id',
    'prescription_id',
    'medication_schedule_id',
    'current_day',
    'current_state',
    'adherence_rate',
    'current_streak',
    'consecutive_missed_days',
    'status',
    'started_at',
    'completed_at',
])]
class RecoveryJourney extends Model
{
    use HasFactory;

    public const STATE_SEED = 'S0_SEED';

    public const STATE_SPROUT = 'S1_SPROUT';

    public const STATE_GROWING = 'S2_GROWING';

    public const STATE_BLOOM = 'S3_BLOOM';

    public const STATE_WILTED = 'W_WILTED';

    /**
     * @var array<int, string>
     */
    protected $appends = ['total_days'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_day' => 'integer',
            'adherence_rate' => 'float',
            'current_streak' => 'integer',
            'consecutive_missed_days' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /** @return BelongsTo<Prescription, $this> */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /** @return BelongsTo<MedicationSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MedicationSchedule::class, 'medication_schedule_id');
    }

    /** @return HasMany<RecoveryReward, $this> */
    public function rewards(): HasMany
    {
        return $this->hasMany(RecoveryReward::class);
    }

    public function getTotalDaysAttribute(): int
    {
        return (int) ($this->schedule?->duration_days ?? 7);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isWilted(): bool
    {
        return $this->current_state === self::STATE_WILTED;
    }
}

