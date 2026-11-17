<?php

namespace App\Http\Controllers;

use App\Models\BloodDonor;
use App\Models\BloodRequest;
use Illuminate\View\View;

class BloodDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'Total blood requests' => BloodRequest::count(),
            'Pending requests' => BloodRequest::where('status', 'pending')->count(),
            'Active requests' => BloodRequest::active()->count(),
            'Critical requests' => BloodRequest::active()->where('emergency_level', 'critical')->count(),
            'Fulfilled requests' => BloodRequest::where('status', 'fulfilled')->count(),
            'Registered donors' => BloodDonor::count(),
            'Available donors' => BloodDonor::where('availability_status', 'available')->count(),
        ];

        return view('blood-connect.admin.dashboard', compact('stats'));
    }
}
