<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['blood_request_id', 'blood_donor_id', 'status', 'contacted_at', 'response_at', 'notes'])]
class BloodRequestDonor extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['contacted_at' => 'datetime', 'response_at' => 'datetime'];
    }

    public function bloodRequest(): BelongsTo
    {
        return $this->belongsTo(BloodRequest::class);
    }

    public function bloodDonor(): BelongsTo
    {
        return $this->belongsTo(BloodDonor::class);
    }
}
