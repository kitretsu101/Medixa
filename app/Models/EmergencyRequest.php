<?php

namespace App\Models;

use Database\Factories\EmergencyRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['patient_name', 'contact_phone', 'location', 'issue_type', 'status', 'handled_by', 'note'])]
class EmergencyRequest extends Model
{
    /** @use HasFactory<EmergencyRequestFactory> */
    use HasFactory;

    public const ISSUE_TYPES = ['accident', 'cardiac', 'breathing', 'other'];

    public const STATUSES = ['pending', 'acknowledged', 'resolved'];

    /** @return BelongsTo<User, $this> */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
