<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'full_name', 'blood_group', 'phone', 'district', 'area', 'last_donation_date', 'availability_status', 'public_profile', 'contact_consent'])]
#[Hidden(['user_id', 'phone', 'phone_verified_at'])]
class BloodDonor extends Model
{
    use HasFactory;

    public const AVAILABILITY = ['available', 'temporarily_unavailable', 'unavailable'];

    protected function casts(): array
    {
        return ['last_donation_date' => 'date', 'phone_verified_at' => 'datetime', 'public_profile' => 'boolean', 'contact_consent' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(BloodDonationRecord::class);
    }

    public function interests(): HasMany
    {
        return $this->hasMany(BloodRequestDonor::class);
    }

    public function isDiscoverable(): bool
    {
        return $this->availability_status === 'available' && $this->public_profile && $this->contact_consent;
    }
}
