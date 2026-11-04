<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'recovery_journey_id',
    'patient_id',
    'reward_type',
    'badge_name',
    'points',
    'description',
    'unlocked_at',
])]
class RecoveryReward extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'unlocked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<RecoveryJourney, $this> */
    public function journey(): BelongsTo
    {
        return $this->belongsTo(RecoveryJourney::class, 'recovery_journey_id');
    }

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}

