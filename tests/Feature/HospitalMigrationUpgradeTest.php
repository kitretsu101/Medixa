<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HospitalMigrationUpgradeTest extends TestCase
{
    public function test_existing_mysql_records_and_role_enum_survive_the_additive_upgrade_unassigned(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $database = 'medixa_upgrade_'.bin2hex(random_bytes(6));
        $administrator = DB::connection();
        $administrator->statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        try {
            config(['database.connections.hospital_upgrade' => array_replace(config('database.connections.mysql'), ['database' => $database])]);
            $newMigration = '2026_11_23_132100_add_hospital_appointment_approval_workflow.php';
            $oldPaths = array_values(array_map(
                fn (string $path): string => 'database/migrations/'.basename($path),
                array_filter(glob(database_path('migrations/*.php')), fn (string $path): bool => basename($path) < $newMigration),
            ));
            $this->artisan('migrate', ['--database' => 'hospital_upgrade', '--path' => $oldPaths, '--force' => true])->assertSuccessful();
            $connection = DB::connection('hospital_upgrade');
            $doctorId = $connection->table('users')->insertGetId(User::factory()->raw(['role' => 'doctor']));
            $patientId = $connection->table('users')->insertGetId(User::factory()->raw(['role' => 'patient']));
            $appointmentId = $connection->table('appointments')->insertGetId(Appointment::factory()->raw([
                'patient_id' => $patientId, 'doctor_id' => $doctorId, 'status' => 'confirmed',
            ]));
            $beforeUser = (array) $connection->table('users')->find($doctorId);
            $beforeAppointment = (array) $connection->table('appointments')->find($appointmentId);
            $beforeRole = $connection->selectOne("SHOW COLUMNS FROM users LIKE 'role'")->Type;

            $this->artisan('migrate', ['--database' => 'hospital_upgrade', '--path' => ['database/migrations/'.$newMigration], '--force' => true])->assertSuccessful();
            $afterUser = (array) $connection->table('users')->find($doctorId);
            $afterAppointment = (array) $connection->table('appointments')->find($appointmentId);
            $this->assertNull($afterUser['hospital_id']);
            $this->assertNull($afterAppointment['hospital_id']);
            $this->assertNull($afterAppointment['reason']);
            unset($afterUser['hospital_id'], $afterAppointment['hospital_id'], $afterAppointment['reason']);
            $this->assertSame($beforeUser, $afterUser);
            $this->assertSame($beforeAppointment, $afterAppointment);
            $this->assertSame($beforeRole, $connection->selectOne("SHOW COLUMNS FROM users LIKE 'role'")->Type);
            $this->assertSame(2, $connection->table('users')->count());
            $this->assertSame(1, $connection->table('appointments')->count());
            $this->assertSame(0, $connection->table('hospitals')->count());

            $this->artisan('migrate:rollback', ['--database' => 'hospital_upgrade', '--path' => ['database/migrations/'.$newMigration], '--force' => true])->assertSuccessful();
            $this->assertFalse($connection->getSchemaBuilder()->hasTable('hospitals'));
            $this->assertSame($beforeUser, (array) $connection->table('users')->find($doctorId));
            $this->assertSame($beforeAppointment, (array) $connection->table('appointments')->find($appointmentId));
        } finally {
            DB::purge('hospital_upgrade');
            $administrator->statement('DROP DATABASE `'.$database.'`');
        }
    }
}
