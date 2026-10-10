<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicController extends Controller
{
    /**
     * Homepage — hero section + department grid with doctor counts.
     */
    public function home(): View
    {
        $departments = Department::withCount('doctorProfiles')->get();

        return view('public.home', compact('departments'));
    }

    /**
     * Doctor directory, filterable by department slug/id.
     */
    public function doctors(Request $request): View
    {
        $departments = Department::orderBy('name')->get();

        $doctorsQuery = User::doctors()
            ->with(['doctorProfile.department'])
            ->whereHas('doctorProfile');

        $departmentId = $request->integer('department') ?: $request->integer('department_id');

        if ($departmentId) {
            $doctorsQuery->whereHas('doctorProfile', function ($query) use ($departmentId): void {
                $query->where('department_id', $departmentId);
            });
        }

        $doctors = $doctorsQuery->get();
        $selectedDepartmentId = $departmentId ?: null;

        return view('public.doctors', compact('doctors', 'departments', 'selectedDepartmentId'));
    }

    /**
     * Individual doctor profile — bio, qualifications, department.
     */
    public function doctorProfile(User $doctor): View
    {
        abort_unless($doctor->isDoctor(), 404);

        $doctor->load('doctorProfile.department');

        return view('public.doctor-profile', compact('doctor'));
    }

    /**
     * Department detail page — info + list of doctors.
     */
    public function departmentDetail(Department $department): View
    {
        $department->load('doctorProfiles.user');

        return view('public.department-detail', compact('department'));
    }
}
