<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['request_code', 'submission_hash', 'user_id', 'patient_name', 'blood_group', 'units_required', 'units_fulfilled', 'hospital_name', 'district', 'area', 'required_date', 'contact_name', 'contact_number', 'emergency_level', 'description', 'status', 'contact_consent', 'verified_at', 'fulfilled_at', 'expires_at'])]
#[Hidden(['user_id', 'patient_name', 'contact_name', 'contact_number', 'description', 'submission_hash'])]
class BloodRequest extends Model
{
    use HasFactory;

    public const GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public const LEVELS = ['normal', 'urgent', 'critical'];

    protected function casts(): array
    {
        return ['contact_consent' => 'boolean', 'required_date' => 'date', 'verified_at' => 'datetime', 'fulfilled_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'request_code';
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', 'active')->whereNotNull('verified_at')->whereNull('fulfilled_at')->where('expires_at', '>', now())->whereDate('required_date', '>=', today())->whereColumn('units_fulfilled', '<', 'units_required');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->verified_at !== null && $this->fulfilled_at === null && $this->expires_at->isFuture() && $this->required_date->gte(today()) && $this->units_fulfilled < $this->units_required;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interests(): HasMany
    {
        return $this->hasMany(BloodRequestDonor::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(BloodDonationRecord::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(BloodRequestEvent::class);
    }
}
