<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonorPasswordReset extends Model
{
    use HasFactory;

    protected $fillable = ['donor_id', 'token_hash', 'expires_at'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(BloodDonor::class, 'donor_id');
    }
}
