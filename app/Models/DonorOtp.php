<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonorOtp extends Model
{
    use HasFactory;

    protected $fillable = ['donor_id', 'code_hash', 'expires_at', 'attempts'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'attempts' => 'integer'];
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(BloodDonor::class, 'donor_id');
    }
}
