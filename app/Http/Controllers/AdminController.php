<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\LabReport;
use App\Models\User;
use App\Services\OpdAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    // ─── Dashboard ───────────────────────────────────────────────────

    /**
     * Admin dashboard — system overview stats.
     */
    public function dashboard(): View
    {
        $stats = [
            'total_patients' => User::patients()->count(),
            'total_doctors' => User::doctors()->count(),
            'total_departments' => Department::count(),
            'total_appointments' => Appointment::count(),
            'pending_appointments' => Appointment::where('status', 'pending')->count(),
            'completed_appointments' => Appointment::where('status', 'completed')->count(),
            'total_lab_reports' => LabReport::count(),
            'pending_reviews' => LabReport::where('review_status', 'pending')->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    public function analytics(OpdAnalyticsService $analytics): View
    {
        return view('admin.analytics', [
            'metrics' => $analytics->dashboard(),
        ]);
    }

    // ─── Departments CRUD ────────────────────────────────────────────

    /**
     * List all departments.
     */
    public function departments(): View
    {
        $departments = Department::withCount('doctorProfiles')->get();

        return view('admin.departments', compact('departments'));
    }

    /**
     * Show create department form.
     */
    public function createDepartment(): View
    {
        return view('admin.department-form', ['department' => null]);
    }

    /**
     * Store a new department.
     */
    public function storeDepartment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        Department::create($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    /**
     * Show edit department form.
     */
    public function editDepartment(Department $department): View
    {
        return view('admin.department-form', compact('department'));
    }

    /**
     * Update department.
     */
    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name,'.$department->id],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $department->update($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    /**
     * Delete department (only if no doctors assigned).
     */
    public function destroyDepartment(Department $department): RedirectResponse
    {
        if ($department->doctorProfiles()->exists()) {
            return back()->with('error', 'Cannot delete department with assigned doctors.');
        }

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted.');
    }

    // ─── Doctors CRUD ────────────────────────────────────────────────

    /**
     * List all doctors with their profiles.
     */
    public function doctors(): View
    {
        $doctors = User::doctors()
            ->with(['doctorProfile.department', 'liveQueue'])
            ->paginate(15);

        return view('admin.doctors', compact('doctors'));
    }

    /**
     * Show create doctor form.
     */
    public function createDoctor(): View
    {
        $departments = Department::all();

        return view('admin.doctor-form', ['doctor' => null, 'departments' => $departments]);
    }

    /**
     * Store a new doctor and their profile.
     */
    public function storeDoctor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'department_id' => ['required', 'exists:departments,id'],
            'qualification' => ['required', 'string', 'max:500'],
            'designation' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'doctor',
            'email_verified_at' => now(),
        ]);

        DoctorProfile::create([
            'user_id' => $user->id,
            'department_id' => $validated['department_id'],
            'qualification' => $validated['qualification'],
            'designation' => $validated['designation'],
        ]);

        return redirect()->route('admin.doctors.index')
            ->with('success', 'Doctor registered successfully.');
    }

    /**
     * Show edit doctor form.
     */
    public function editDoctor(User $doctor): View
    {
        if (! $doctor->isDoctor()) {
            abort(Response::HTTP_NOT_FOUND, 'Doctor not found.');
        }

        $doctor->load('doctorProfile');
        $departments = Department::all();

        return view('admin.doctor-form', compact('doctor', 'departments'));
    }

    /**
     * Update a doctor and their profile.
     */
    public function updateDoctor(Request $request, User $doctor): RedirectResponse
    {
        if (! $doctor->isDoctor()) {
            abort(Response::HTTP_NOT_FOUND, 'Doctor not found.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$doctor->id],
            'department_id' => ['required', 'exists:departments,id'],
            'qualification' => ['required', 'string', 'max:500'],
            'designation' => ['required', 'string', 'max:255'],
        ]);

        $doctor->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $doctor->doctorProfile()->updateOrCreate(
            ['user_id' => $doctor->id],
            [
                'department_id' => $validated['department_id'],
                'qualification' => $validated['qualification'],
                'designation' => $validated['designation'],
            ]
        );

        return redirect()->route('admin.doctors.index')
            ->with('success', 'Doctor updated successfully.');
    }

    /**
     * Delete a doctor user and their profile.
     */
    public function destroyDoctor(User $doctor): RedirectResponse
    {
        if (! $doctor->isDoctor()) {
            abort(Response::HTTP_NOT_FOUND, 'Doctor not found.');
        }

        $doctor->delete();

        return redirect()->route('admin.doctors.index')
            ->with('success', 'Doctor account deleted.');
    }

    // ─── Schedule Management ─────────────────────────────────────────

    /**
     * View doctor schedule and queue state overview.
     */
    public function schedule(): View
    {
        $doctors = User::doctors()
            ->with(['doctorProfile.department', 'liveQueue'])
            ->get();

        // Today's appointment counts per doctor
        $todayAppointments = Appointment::where('appointment_date', now()->toDateString())
            ->select('doctor_id', DB::raw('COUNT(*) as total'), DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"))
            ->groupBy('doctor_id')
            ->get()
            ->keyBy('doctor_id');

        return view('admin.schedule', compact('doctors', 'todayAppointments'));
    }

    // ─── Audit Oversight (AGGREGATE ONLY) ────────────────────────────

    /**
     * Aggregate-only audit oversight page.
     *
     * Shows COUNTS and PATTERNS of audit logs — never individual
     * patient record contents. Admins see "Dr. X accessed 42 records
     * this week", NOT what those records contained.
     */
    public function auditOversight(Request $request): View
    {
        // Per-doctor access counts this week
        $weeklyAccessByDoctor = AuditLog::query()
            ->where('accessed_at', '>=', now()->subWeek())
            ->join('users', 'audit_log.actor_id', '=', 'users.id')
            ->select(
                'users.id as doctor_id',
                'users.name as doctor_name',
                DB::raw('COUNT(*) as access_count'),
                DB::raw('COUNT(DISTINCT audit_log.patient_id) as unique_patients'),
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('access_count')
            ->get();

        // Daily access trend (last 30 days)
        $dailyTrend = AuditLog::query()
            ->where('accessed_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(accessed_at) as date'),
                DB::raw('COUNT(*) as total_accesses'),
            )
            ->groupBy(DB::raw('DATE(accessed_at)'))
            ->orderBy('date')
            ->get();

        // Resource type breakdown
        $resourceBreakdown = AuditLog::query()
            ->where('accessed_at', '>=', now()->subWeek())
            ->select('resource_type', DB::raw('COUNT(*) as count'))
            ->groupBy('resource_type')
            ->get();

        // Total stats
        $totalStats = [
            'total_accesses' => AuditLog::count(),
            'this_week' => AuditLog::where('accessed_at', '>=', now()->subWeek())->count(),
            'unique_actors' => AuditLog::distinct('actor_id')->count('actor_id'),
            'unique_patients_accessed' => AuditLog::distinct('patient_id')->count('patient_id'),
        ];

        return view('admin.audit-oversight', compact(
            'weeklyAccessByDoctor',
            'dailyTrend',
            'resourceBreakdown',
            'totalStats',
        ));
    }
}
