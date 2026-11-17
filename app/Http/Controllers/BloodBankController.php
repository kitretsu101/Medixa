<?php

namespace App\Http\Controllers;

use App\Models\BloodRequest;
use Illuminate\View\View;

class BloodBankController extends Controller
{
    public function index(): View
    {
        return view('blood-connect.index', [
            'activeCount' => BloodRequest::active()->count(),
            'criticalCount' => BloodRequest::active()->where('emergency_level', 'critical')->count(),
            'requests' => BloodRequest::active()->latest()->orderByDesc('id')->limit(6)->get(),
        ]);
    }
}
