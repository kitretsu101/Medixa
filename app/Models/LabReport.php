<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

#[Fillable(['patient_id', 'doctor_id', 'report_title', 'file_path', 'review_status', 'clinical_outcome', 'reviewed_at', 'review_note', 'upload_status', 'uploaded_by', 'uploaded_at', 'reference'])]
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
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (LabReport $report): void {
            if ($report->reference !== null) {
                return;
            }

            $report->forceFill([
                'reference' => sprintf('LAB-%s-%05d', $report->created_at->format('Y'), $report->id),
            ])->saveQuietly();
        });
    }

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

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function patientReference(): string
    {
        return sprintf('PAT-%05d', $this->patient_id);
    }

    public function isPendingUpload(): bool
    {
        return $this->upload_status === 'pending_upload' && $this->file_path === null;
    }

    /**
     * Generate a signed, short-lived (2 minute) temporary download URL.
     */
    public function getTemporaryDownloadUrl(int $minutes = 2): string
    {
        return URL::signedRoute(
            'lab-reports.download',
            ['labReport' => $this->id],
            now()->addMinutes($minutes),
        );
    }
}
