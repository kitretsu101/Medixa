<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['actor_id', 'patient_id', 'resource_type', 'resource_id', 'consent_token_id', 'accessed_at'])]
class AuditLog extends Model
{
    /** The table associated with the model. */
    protected $table = 'audit_log';

    /** Immutable log — no auto-timestamps. */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accessed_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /** @return BelongsTo<ConsentToken, $this> */
    public function consentToken(): BelongsTo
    {
        return $this->belongsTo(ConsentToken::class, 'consent_token_id');
    }
}
