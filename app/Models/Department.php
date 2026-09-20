<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['name', 'description'])]
class Department extends Model
{
    // ─── Relationships ────────────────────────────────────────────────

    /** @return HasMany<DoctorProfile, $this> */
    public function doctorProfiles(): HasMany
    {
        return $this->hasMany(DoctorProfile::class);
    }

    /**
     * Doctors (User instances) belonging to this department via doctor_profiles.
     *
     * @return HasManyThrough<User, DoctorProfile, $this>
     */
    public function doctors(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, DoctorProfile::class, 'department_id', 'id', 'id', 'user_id');
    }
}
