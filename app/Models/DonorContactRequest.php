<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonorContactRequest extends Model
{
    use HasFactory;

    protected $fillable = ['requester_name', 'requester_phone', 'blood_group_needed', 'units_needed', 'hospital_name', 'location', 'required_date', 'emergency_level', 'message'];

    protected $hidden = ['requester_phone', 'requester_name', 'message', 'location', 'hospital_name'];

    public const STATUSES = ['pending', 'contacted', 'closed'];

    protected function casts(): array
    {
        return ['required_date' => 'date'];
    }

    public function bloodDonor(): BelongsTo
    {
        return $this->belongsTo(BloodDonor::class, 'blood_donor_id');
    }
}
