<?php

namespace App\Http\Controllers;

use App\Models\BloodDonor;
use Illuminate\View\View;

class BloodDonorManagementController extends Controller
{
    public function index(): View
    {
        return view('blood-connect.admin.donors', ['donors' => BloodDonor::query()->latest()->orderByDesc('id')->paginate(20)]);
    }
}
