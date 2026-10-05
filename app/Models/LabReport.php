<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

#[Fillable(['patient_id', 'doctor_id', 'report_title', 'file_path', 'review_status', 'uploaded_at'])]
class LabReport extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /** @return BelongsTo<User, $this> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Generate a signed, short-lived (2 minute) temporary download URL.
     */
    public function getTemporaryDownloadUrl(int $minutes = 2): string
    {
        return URL::signedRoute(
            'patient.lab-reports.download',
            ['labReport' => $this->id],
            now()->addMinutes($minutes),
        );
    }
}
