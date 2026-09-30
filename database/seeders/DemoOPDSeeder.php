<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\LabReport;
use App\Models\User;
use App\Services\ConsentService;
use Illuminate\Database\Seeder;

class DemoOPDSeeder extends Seeder
{
    public function run(): void
    {
        $doctor = User::where('email', 'arman.hossain@medixa.test')->first();
        $patient = User::where('email', 'rahima.khatun@patient.test')->first();

        if ($doctor && $patient) {
            $appointment = Appointment::firstOrCreate([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'serial_number' => 1,
            ], [
                'appointment_date' => now()->toDateString(),
                'status' => 'pending',
            ]);

            $labReport = LabReport::firstOrCreate([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'report_title' => 'Complete Blood Count (CBC) with Platelets',
            ], [
                'file_path' => 'reports/sample_cbc.pdf',
                'review_status' => 'pending',
                'uploaded_at' => now(),
            ]);

            $consentService = app(ConsentService::class);
            $token = $consentService->findValidToken($doctor->id, 'lab_report', $labReport->id);

            if (! $token) {
                $consentService->issue(
                    patientId: $patient->id,
                    granteeId: $doctor->id,
                    resourceType: 'lab_report',
                    resourceId: $labReport->id,
                    ttlMinutes: 120
                );
            }
        }
    }
}
