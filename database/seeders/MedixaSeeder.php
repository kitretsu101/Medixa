<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AssistanceRequest;
use App\Models\AuditLog;
use App\Models\ConsentToken;
use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\LabReport;
use App\Models\LiveQueue;
use App\Models\PatientProfile;
use App\Models\Prescription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MedixaSeeder extends Seeder
{
    /**
     * Seed comprehensive, realistic demo data across ALL tables:
     * - Admin user
     * - 4 Departments
     * - 6 Doctors with profiles & live queues
     * - 16+ Patients with medical profiles
     * - Appointments in various states (pending, confirmed, completed, cancelled)
     * - Lab reports with pending & reviewed statuses
     * - Prescriptions with encrypted instructions and medicine regimens
     * - Assistance requests for OPD patients
     * - Consent tokens (Active, Expired, Revoked)
     * - Audit log records for compliance & oversight tracking
     */
    public function run(): void
    {
        // ── 1. Admin User ──────────────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@medixa.test'],
            [
                'name' => 'Hospital Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // ── 2. Departments ────────────────────────────────────────────────
        $departmentsData = [
            [
                'name' => 'Cardiology',
                'description' => 'Comprehensive diagnosis and treatment for heart conditions including coronary artery disease, arrhythmias, heart failure, and preventive cardiology. Equipped with modern echo and catheterisation labs.',
            ],
            [
                'name' => 'Neurology',
                'description' => 'Specialised care in disorders of the brain, spinal cord, peripheral nerves, and muscles. Services include stroke management, epilepsy clinics, and neuro-rehabilitation.',
            ],
            [
                'name' => 'Orthopedics',
                'description' => 'Advanced surgical and conservative care for musculoskeletal conditions — joint replacement, sports medicine, spine surgery, and fracture rehabilitation.',
            ],
            [
                'name' => 'Pediatrics',
                'description' => 'Comprehensive child healthcare from neonatal through adolescence, including developmental assessments, immunisation clinics, and acute pediatric care.',
            ],
        ];

        $deptModels = [];
        foreach ($departmentsData as $data) {
            $deptModels[$data['name']] = Department::firstOrCreate(['name' => $data['name']], $data);
        }

        // ── 3. Doctors & Profiles & Live Queues ────────────────────────────
        $doctorsData = [
            [
                'name' => 'Dr. Arman Hossain',
                'email' => 'arman.hossain@medixa.test',
                'dept' => 'Cardiology',
                'qualification' => 'MBBS, MD (Cardiology), FCPS',
                'designation' => 'Senior Consultant Cardiologist',
                'patients_seen' => 3240,
                'queue_token' => 7,
                'is_present' => true,
            ],
            [
                'name' => 'Dr. Nadia Rahman',
                'email' => 'nadia.rahman@medixa.test',
                'dept' => 'Cardiology',
                'qualification' => 'MBBS, MD (Cardiology), MRCP (UK)',
                'designation' => 'Interventional Cardiologist',
                'patients_seen' => 1870,
                'queue_token' => 4,
                'is_present' => true,
            ],
            [
                'name' => 'Dr. Farhan Karim',
                'email' => 'farhan.karim@medixa.test',
                'dept' => 'Neurology',
                'qualification' => 'MBBS, MD (Neurology), PhD',
                'designation' => 'Chief Neurologist',
                'patients_seen' => 4150,
                'queue_token' => 11,
                'is_present' => true,
            ],
            [
                'name' => 'Dr. Sumaiya Begum',
                'email' => 'sumaiya.begum@medixa.test',
                'dept' => 'Neurology',
                'qualification' => 'MBBS, FCPS (Neurology)',
                'designation' => 'Consultant Neurologist',
                'patients_seen' => 980,
                'queue_token' => 2,
                'is_present' => false,
            ],
            [
                'name' => 'Dr. Iqbal Chowdhury',
                'email' => 'iqbal.chowdhury@medixa.test',
                'dept' => 'Orthopedics',
                'qualification' => 'MBBS, MS (Orthopedics), FRCS',
                'designation' => 'Senior Orthopedic Surgeon',
                'patients_seen' => 5610,
                'queue_token' => 9,
                'is_present' => true,
            ],
            [
                'name' => 'Dr. Tasneem Akter',
                'email' => 'tasneem.akter@medixa.test',
                'dept' => 'Orthopedics',
                'qualification' => 'MBBS, MS (Orthopedics), Fellowship Sports Med',
                'designation' => 'Sports & Trauma Specialist',
                'patients_seen' => 1420,
                'queue_token' => 0,
                'is_present' => false,
            ],
        ];

        $doctorUsers = [];
        foreach ($doctorsData as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'name' => $d['name'],
                    'password' => Hash::make('password'),
                    'role' => 'doctor',
                    'email_verified_at' => now(),
                ]
            );

            DoctorProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'department_id' => $deptModels[$d['dept']]->id,
                    'qualification' => $d['qualification'],
                    'designation' => $d['designation'],
                    'patients_seen_count' => $d['patients_seen'],
                ]
            );

            LiveQueue::firstOrCreate(
                ['doctor_id' => $user->id],
                [
                    'current_token' => $d['queue_token'],
                    'is_present' => $d['is_present'],
                ]
            );

            $doctorUsers[] = $user;
        }

        // ── 4. Patients (16+ realistic patients) ───────────────────────────
        $patientsData = [
            ['name' => 'Rahima Khatun',   'email' => 'rahima.khatun@patient.test',   'dob' => '1985-03-12', 'blood_group' => 'A+',  'emergency_contact' => '01711-234567', 'medical_history' => 'Hypertension, managed with Amlodipine 5mg daily.'],
            ['name' => 'Mahbub Alam',     'email' => 'mahbub.alam@patient.test',     'dob' => '1992-07-28', 'blood_group' => 'B+',  'emergency_contact' => '01822-345678', 'medical_history' => 'Type 2 Diabetes Mellitus. On Metformin 500mg twice daily.'],
            ['name' => 'Shirin Sultana', 'email' => 'shirin.sultana@patient.test', 'dob' => '1975-11-05', 'blood_group' => 'O+',  'emergency_contact' => '01933-456789', 'medical_history' => 'Migraine history, episodic. Takes Sumatriptan PRN.'],
            ['name' => 'Kamal Uddin',    'email' => 'kamal.uddin@patient.test',    'dob' => '1980-01-19', 'blood_group' => 'AB+', 'emergency_contact' => '01644-567890', 'medical_history' => 'Lumbar disc prolapse — L4/L5. Physiotherapy ongoing.'],
            ['name' => 'Nasrin Parvin',  'email' => 'nasrin.parvin@patient.test',  'dob' => '1998-09-30', 'blood_group' => 'A-',  'emergency_contact' => '01755-678901', 'medical_history' => 'Seasonal allergic rhinitis. Uses Loratadine 10mg.'],
            ['name' => 'Rezaul Islam',   'email' => 'rezaul.islam@patient.test',   'dob' => '1965-04-14', 'blood_group' => 'B-',  'emergency_contact' => '01866-789012', 'medical_history' => 'Ischaemic heart disease. Post-PCI (2022). On dual antiplatelet therapy.'],
            ['name' => 'Tahmina Akter',  'email' => 'tahmina.akter@patient.test',  'dob' => '2000-12-08', 'blood_group' => 'O-',  'emergency_contact' => '01977-890123', 'medical_history' => 'No significant past medical history. Routine OPD visit.'],
            ['name' => 'Shahidul Haque', 'email' => 'shahidul.haque@patient.test', 'dob' => '1970-06-22', 'blood_group' => 'AB-', 'emergency_contact' => '01788-901234', 'medical_history' => 'Epilepsy, well-controlled on Sodium Valproate 500mg BD.'],
            ['name' => 'Farzana Begum',  'email' => 'farzana.begum@patient.test',  'dob' => '1988-02-17', 'blood_group' => 'A+',  'emergency_contact' => '01699-012345', 'medical_history' => 'Osteoarthritis of bilateral knees. Awaiting physiotherapy assessment.'],
            ['name' => 'Monir Ahmed',    'email' => 'monir.ahmed@patient.test',    'dob' => '1955-08-03', 'blood_group' => 'B+',  'emergency_contact' => '01500-123456', 'medical_history' => 'Chronic kidney disease Stage 3. Nephrology follow-up quarterly.'],
            ['name' => 'Khadija Bibi',   'email' => 'khadija.bibi@patient.test',   'dob' => '1962-09-14', 'blood_group' => 'O+',  'emergency_contact' => '01712-345678', 'medical_history' => 'Osteoporosis and recurrent lower back pain. Takes Calcium + Vitamin D.'],
            ['name' => 'Tariqul Hasan',  'email' => 'tariqul.hasan@patient.test',  'dob' => '1983-11-20', 'blood_group' => 'A+',  'emergency_contact' => '01833-456789', 'medical_history' => 'Post-cholecystectomy (2023). Mild fatty liver on ultrasound.'],
            ['name' => 'Salma Khatun',   'email' => 'salma.khatun@patient.test',   'dob' => '1995-05-18', 'blood_group' => 'B+',  'emergency_contact' => '01944-567890', 'medical_history' => 'Iron deficiency anaemia. Oral iron supplement prescribed.'],
            ['name' => 'Anwar Hossain',  'email' => 'anwar.hossain@patient.test',  'dob' => '1978-04-09', 'blood_group' => 'AB+', 'emergency_contact' => '01655-678901', 'medical_history' => 'Bronchial asthma. Inhaler Salbutamol as needed.'],
            ['name' => 'Rokeya Sultana', 'email' => 'rokeya.sultana@patient.test', 'dob' => '1972-10-31', 'blood_group' => 'O-',  'emergency_contact' => '01766-789012', 'medical_history' => 'Hypothyroidism on Levothyroxine 50mcg daily.'],
            ['name' => 'Zakir Mia',      'email' => 'zakir.mia@patient.test',      'dob' => '1987-01-25', 'blood_group' => 'A-',  'emergency_contact' => '01877-890123', 'medical_history' => 'Cervical radiculopathy. Ergonomic adjustment and physio recommended.'],
        ];

        $patientUsers = [];
        foreach ($patientsData as $p) {
            $user = User::firstOrCreate(
                ['email' => $p['email']],
                [
                    'name' => $p['name'],
                    'password' => Hash::make('password'),
                    'role' => 'patient',
                    'email_verified_at' => now(),
                ]
            );

            PatientProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'dob' => $p['dob'],
                    'blood_group' => $p['blood_group'],
                    'emergency_contact' => $p['emergency_contact'],
                    'medical_history' => $p['medical_history'],
                ]
            );

            $patientUsers[] = $user;
        }

        // ── 5. Appointments in Various States ──────────────────────────────
        // States: pending, confirmed, completed, cancelled
        $statuses = ['completed', 'pending', 'confirmed', 'cancelled', 'completed'];
        $createdAppointments = [];

        foreach ($patientUsers as $idx => $patient) {
            $doctor = $doctorUsers[$idx % count($doctorUsers)];
            $status = $statuses[$idx % count($statuses)];
            $dateOffset = ($idx % 5) - 2; // -2 to +2 days from today
            $apptDate = Carbon::today()->addDays($dateOffset)->toDateString();

            $appointment = Appointment::firstOrCreate(
                [
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'appointment_date' => $apptDate,
                ],
                [
                    'serial_number' => ($idx % 15) + 1,
                    'status' => $status,
                ]
            );

            $createdAppointments[] = $appointment;
        }

        // ── 6. Prescriptions for Completed Appointments ─────────────────────
        $prescriptions = [];
        foreach ($createdAppointments as $appt) {
            if ($appt->status === 'completed') {
                $prescription = Prescription::firstOrCreate(
                    ['appointment_id' => $appt->id],
                    [
                        'patient_id' => $appt->patient_id,
                        'doctor_id' => $appt->doctor_id,
                        'diagnosis' => 'Clinical evaluation confirmed stable condition. Recommended lifestyle modification and supportive medication.',
                        'medicines' => [
                            ['name' => 'Amlodipine Besylate', 'dosage' => '5mg', 'frequency' => 'Once daily morning', 'duration' => '30 days'],
                            ['name' => 'Paracetamol', 'dosage' => '500mg', 'frequency' => '1 tablet SOS for headache/pain', 'duration' => '5 days'],
                        ],
                        'instructions' => 'Strict low-sodium diet (<2g/day). Maintain regular physical activity of at least 30 minutes daily. Follow up in 4 weeks.',
                    ]
                );
                $prescriptions[] = $prescription;
            }
        }

        // ── 7. Lab Reports (Pending & Reviewed) ────────────────────────────
        $sampleReports = [
            ['title' => 'Complete Blood Count (CBC) with Platelets', 'path' => 'reports/sample_cbc.pdf', 'status' => 'pending'],
            ['title' => '12-Lead Electrocardiogram (ECG)', 'path' => 'reports/ecg_cardio.pdf', 'status' => 'reviewed'],
            ['title' => 'Fasting Blood Glucose & HbA1c Panel', 'path' => 'reports/hba1c_diab.pdf', 'status' => 'reviewed'],
            ['title' => 'MRI Brain T1/T2 Axial Scan', 'path' => 'reports/mri_brain.pdf', 'status' => 'pending'],
            ['title' => 'Digital Radiography (X-Ray) Lumbar Spine AP/LAT', 'path' => 'reports/xray_spine.pdf', 'status' => 'reviewed'],
            ['title' => 'Comprehensive Lipid Profile', 'path' => 'reports/lipid_profile.pdf', 'status' => 'pending'],
        ];

        $createdReports = [];
        foreach ($patientUsers as $idx => $patient) {
            if ($idx < 8) {
                $doc = $doctorUsers[$idx % count($doctorUsers)];
                $repData = $sampleReports[$idx % count($sampleReports)];

                $report = LabReport::firstOrCreate(
                    [
                        'patient_id' => $patient->id,
                        'doctor_id' => $doc->id,
                        'report_title' => $repData['title'],
                    ],
                    [
                        'file_path' => $repData['path'],
                        'review_status' => $repData['status'],
                        'uploaded_at' => Carbon::now()->subDays($idx * 2 + 1),
                    ]
                );

                $createdReports[] = $report;
            }
        }

        // ── 8. Assistance Requests ─────────────────────────────────────────
        $assistanceCategories = ['wheelchair', 'stretcher', 'nurse_escort', 'visual_guidance'];
        foreach (array_slice($patientUsers, 0, 4) as $k => $patient) {
            AssistanceRequest::firstOrCreate(
                [
                    'patient_id' => $patient->id,
                    'category' => $assistanceCategories[$k],
                ],
                [
                    'doctor_id' => $doctorUsers[$k % count($doctorUsers)]->id,
                    'notes' => 'Patient requires assistance from OPD arrival to consultation room.',
                    'status' => $k % 2 === 0 ? 'pending' : 'completed',
                ]
            );
        }

        // ── 9. Consent Tokens (Active, Expired, Revoked) ───────────────────
        $createdTokens = [];

        // Active tokens (Valid for next 48h to 7 days)
        foreach (array_slice($createdReports, 0, 4) as $idx => $report) {
            $token = ConsentToken::firstOrCreate(
                [
                    'patient_id' => $report->patient_id,
                    'grantee_id' => $report->doctor_id,
                    'resource_type' => 'lab_report',
                    'resource_id' => $report->id,
                    'revoked_at' => null,
                ],
                [
                    'issued_at' => Carbon::now()->subHours(2),
                    'expires_at' => Carbon::now()->addDays(2),
                ]
            );
            $createdTokens[] = $token;
        }

        // Expired consent tokens (Issued 30 days ago, expired 10 days ago, unrevoked)
        if (isset($createdReports[4])) {
            $expiredToken = ConsentToken::firstOrCreate(
                [
                    'patient_id' => $createdReports[4]->patient_id,
                    'grantee_id' => $createdReports[4]->doctor_id,
                    'resource_type' => 'lab_report',
                    'resource_id' => $createdReports[4]->id,
                    'expires_at' => Carbon::now()->subDays(10),
                ],
                [
                    'issued_at' => Carbon::now()->subDays(30),
                    'revoked_at' => null,
                ]
            );
            $createdTokens[] = $expiredToken;
        }

        // Revoked consent tokens (Patient explicitly revoked access)
        if (isset($createdReports[5])) {
            $revokedToken = ConsentToken::firstOrCreate(
                [
                    'patient_id' => $createdReports[5]->patient_id,
                    'grantee_id' => $createdReports[5]->doctor_id,
                    'resource_type' => 'lab_report',
                    'resource_id' => $createdReports[5]->id,
                ],
                [
                    'issued_at' => Carbon::now()->subDays(15),
                    'expires_at' => Carbon::now()->addDays(15),
                    'revoked_at' => Carbon::now()->subDays(2),
                ]
            );
            $createdTokens[] = $revokedToken;
        }

        // Active tokens for prescriptions
        foreach (array_slice($prescriptions, 0, 3) as $rx) {
            $rxToken = ConsentToken::firstOrCreate(
                [
                    'patient_id' => $rx->patient_id,
                    'grantee_id' => $rx->doctor_id,
                    'resource_type' => 'prescription',
                    'resource_id' => $rx->id,
                    'revoked_at' => null,
                ],
                [
                    'issued_at' => Carbon::now()->subDays(1),
                    'expires_at' => Carbon::now()->addDays(14),
                ]
            );
            $createdTokens[] = $rxToken;
        }

        // ── 10. Audit Log Entries (Realistic Historical Data) ──────────────
        // Populating audit entries ensures the Audit Oversight page displays rich aggregate metrics
        $auditSamples = [
            // Doctor Arman Hossain accesses
            ['actor' => $doctorUsers[0], 'pt' => $patientUsers[0], 'type' => 'lab_report', 'days_ago' => 1],
            ['actor' => $doctorUsers[0], 'pt' => $patientUsers[0], 'type' => 'prescription', 'days_ago' => 1],
            ['actor' => $doctorUsers[0], 'pt' => $patientUsers[1], 'type' => 'lab_report', 'days_ago' => 2],
            ['actor' => $doctorUsers[0], 'pt' => $patientUsers[2], 'type' => 'appointment', 'days_ago' => 3],
            ['actor' => $doctorUsers[0], 'pt' => $patientUsers[5], 'type' => 'lab_report', 'days_ago' => 4],
            ['actor' => $doctorUsers[0], 'pt' => $patientUsers[6], 'type' => 'prescription', 'days_ago' => 5],
            ['actor' => $doctorUsers[0], 'pt' => $patientUsers[10], 'type' => 'appointment', 'days_ago' => 6],
            // Doctor Nadia Rahman accesses
            ['actor' => $doctorUsers[1], 'pt' => $patientUsers[1], 'type' => 'lab_report', 'days_ago' => 1],
            ['actor' => $doctorUsers[1], 'pt' => $patientUsers[3], 'type' => 'lab_report', 'days_ago' => 2],
            ['actor' => $doctorUsers[1], 'pt' => $patientUsers[4], 'type' => 'prescription', 'days_ago' => 3],
            // Doctor Farhan Karim accesses
            ['actor' => $doctorUsers[2], 'pt' => $patientUsers[2], 'type' => 'lab_report', 'days_ago' => 1],
            ['actor' => $doctorUsers[2], 'pt' => $patientUsers[7], 'type' => 'prescription', 'days_ago' => 2],
            ['actor' => $doctorUsers[2], 'pt' => $patientUsers[8], 'type' => 'lab_report', 'days_ago' => 4],
            ['actor' => $doctorUsers[2], 'pt' => $patientUsers[9], 'type' => 'appointment', 'days_ago' => 5],
            // Doctor Iqbal Chowdhury accesses
            ['actor' => $doctorUsers[4], 'pt' => $patientUsers[3], 'type' => 'lab_report', 'days_ago' => 1],
            ['actor' => $doctorUsers[4], 'pt' => $patientUsers[8], 'type' => 'prescription', 'days_ago' => 3],
            ['actor' => $doctorUsers[4], 'pt' => $patientUsers[11], 'type' => 'lab_report', 'days_ago' => 6],
            // Older records (10 to 25 days ago)
            ['actor' => $doctorUsers[0], 'pt' => $patientUsers[0], 'type' => 'lab_report', 'days_ago' => 12],
            ['actor' => $doctorUsers[1], 'pt' => $patientUsers[2], 'type' => 'prescription', 'days_ago' => 18],
            ['actor' => $doctorUsers[2], 'pt' => $patientUsers[7], 'type' => 'lab_report', 'days_ago' => 22],
            ['actor' => $doctorUsers[4], 'pt' => $patientUsers[4], 'type' => 'appointment', 'days_ago' => 25],
        ];

        foreach ($auditSamples as $log) {
            AuditLog::create([
                'actor_id' => $log['actor']->id,
                'patient_id' => $log['pt']->id,
                'resource_type' => $log['type'],
                'resource_id' => 1,
                'consent_token_id' => $createdTokens[0]->id ?? null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Medixa/OPD-Demo',
                'accessed_at' => Carbon::now()->subDays($log['days_ago'])->subMinutes(rand(10, 400)),
            ]);
        }

        $this->command->info(' Medixa comprehensive demo data seeded across ALL tables:');
        $this->command->info('  • Admin: admin@medixa.test / password');
        $this->command->info('  • 4 Departments, 6 Doctors (with Live Queues), 16 Patients');
        $this->command->info('  • Appointments (pending, confirmed, completed, cancelled)');
        $this->command->info('  • Prescriptions & Lab Reports with 2-minute signed download URLs');
        $this->command->info('  • Active, Expired, and Revoked Consent Tokens');
        $this->command->info('  • Aggregate Audit Log records spanning past 30 days');
    }
}
