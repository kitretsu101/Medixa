<?php

namespace App\Http\Controllers;

use App\Models\BloodDonor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BloodDonorManagementController extends Controller
{
    public function index(): View
    {
        return view('blood-connect.admin.donors', ['donors' => BloodDonor::query()->latest()->orderByDesc('id')->paginate(20)]);
    }

    public function update(Request $request, BloodDonor $bloodDonor): RedirectResponse
    {
        $data = $request->validate(['availability_status' => ['required', Rule::in(BloodDonor::AVAILABILITY)]]);
        $bloodDonor->update($data);

        return back()->with('success', 'Donor availability updated.');
    }
}
