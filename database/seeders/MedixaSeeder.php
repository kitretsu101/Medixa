<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MedixaSeeder extends Seeder
{
    /**
     * Seed 3 departments, 6 doctors (2 per dept), and 10 patients with realistic data.
     */
    public function run(): void
    {
        // ── Departments ───────────────────────────────────────────────

        $departments = [
            [
                'name' => 'Cardiology',
                'description' => 'The Cardiology department provides comprehensive diagnosis and treatment for heart conditions including coronary artery disease, arrhythmias, heart failure, and preventive cardiology. Our specialists use state-of-the-art echocardiography and cardiac catheterisation labs.',
            ],
            [
                'name' => 'Neurology',
                'description' => 'Our Neurology department specialises in disorders of the brain, spinal cord, peripheral nerves, and muscles. Services include stroke management, epilepsy care, headache clinics, Parkinson\'s disease treatment, and neuro-rehabilitation.',
            ],
            [
                'name' => 'Orthopedics',
                'description' => 'The Orthopedics department offers advanced surgical and non-surgical care for musculoskeletal conditions — including joint replacement, sports injury treatment, spine surgery, fracture care, and physiotherapy rehabilitation.',
            ],
        ];

        $departmentModels = [];
        foreach ($departments as $deptData) {
            $departmentModels[] = Department::create($deptData);
        }

        // ── Doctors ───────────────────────────────────────────────────

        $doctors = [
            // Cardiology
            [
                'user' => [
                    'name' => 'Dr. Arman Hossain',
                    'email' => 'arman.hossain@medixa.test',
                    'role' => 'doctor',
                ],
                'profile' => [
                    'department_id' => $departmentModels[0]->id,
                    'qualification' => 'MBBS, MD (Cardiology), FCPS',
                    'designation' => 'Senior Consultant Cardiologist',
                    'patients_seen_count' => 3240,
                ],
            ],
            [
                'user' => [
                    'name' => 'Dr. Nadia Rahman',
                    'email' => 'nadia.rahman@medixa.test',
                    'role' => 'doctor',
                ],
                'profile' => [
                    'department_id' => $departmentModels[0]->id,
                    'qualification' => 'MBBS, MD (Cardiology), MRCP (UK)',
                    'designation' => 'Interventional Cardiologist',
                    'patients_seen_count' => 1870,
                ],
            ],
            // Neurology
            [
                'user' => [
                    'name' => 'Dr. Farhan Karim',
                    'email' => 'farhan.karim@medixa.test',
                    'role' => 'doctor',
                ],
                'profile' => [
                    'department_id' => $departmentModels[1]->id,
                    'qualification' => 'MBBS, MD (Neurology), PhD',
                    'designation' => 'Chief Neurologist',
                    'patients_seen_count' => 4150,
                ],
            ],
            [
                'user' => [
                    'name' => 'Dr. Sumaiya Begum',
                    'email' => 'sumaiya.begum@medixa.test',
                    'role' => 'doctor',
                ],
                'profile' => [
                    'department_id' => $departmentModels[1]->id,
                    'qualification' => 'MBBS, FCPS (Neurology)',
                    'designation' => 'Consultant Neurologist',
                    'patients_seen_count' => 980,
                ],
            ],
            // Orthopedics
            [
                'user' => [
                    'name' => 'Dr. Iqbal Chowdhury',
                    'email' => 'iqbal.chowdhury@medixa.test',
                    'role' => 'doctor',
                ],
                'profile' => [
                    'department_id' => $departmentModels[2]->id,
                    'qualification' => 'MBBS, MS (Orthopedics), FRCS',
                    'designation' => 'Senior Orthopedic Surgeon',
                    'patients_seen_count' => 5610,
                ],
            ],
            [
                'user' => [
                    'name' => 'Dr. Tasneem Akter',
                    'email' => 'tasneem.akter@medixa.test',
                    'role' => 'doctor',
                ],
                'profile' => [
                    'department_id' => $departmentModels[2]->id,
                    'qualification' => 'MBBS, MS (Orthopedics), Fellowship in Sports Medicine',
                    'designation' => 'Sports & Trauma Specialist',
                    'patients_seen_count' => 1420,
                ],
            ],
        ];

        foreach ($doctors as $doctorData) {
            $user = User::create([
                ...$doctorData['user'],
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]);

            DoctorProfile::create([
                ...$doctorData['profile'],
                'user_id' => $user->id,
            ]);
        }

        // ── Patients ──────────────────────────────────────────────────

        $patients = [
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
        ];

        foreach ($patients as $patientData) {
            $user = User::create([
                'name' => $patientData['name'],
                'email' => $patientData['email'],
                'role' => 'patient',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]);

            PatientProfile::create([
                'user_id' => $user->id,
                'dob' => $patientData['dob'],
                'blood_group' => $patientData['blood_group'],
                'emergency_contact' => $patientData['emergency_contact'],
                'medical_history' => $patientData['medical_history'],
            ]);
        }

        $this->command->info('✅ Medixa seeded: 3 departments, 6 doctors, 10 patients.');
    }
}
