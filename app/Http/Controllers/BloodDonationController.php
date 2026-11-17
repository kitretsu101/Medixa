<?php

namespace App\Http\Controllers;

use App\Models\BloodDonationRecord;
use App\Models\BloodRequest;
use App\Services\BloodDonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BloodDonationController extends Controller
{
    public function index(): View
    {
        return view('blood-connect.admin.donations', ['donations' => BloodDonationRecord::with(['bloodRequest', 'bloodDonor', 'verifier'])->latest()->orderByDesc('id')->paginate(20)]);
    }

    public function store(Request $request, BloodRequest $bloodRequest, BloodDonationService $service): RedirectResponse
    {
        $data = $request->validate([
            'blood_donor_id' => ['required', 'integer', 'exists:blood_donors,id'],
            'donation_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.$bloodRequest->created_at->toDateString()],
            'units_donated' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
        $service->record($bloodRequest, $request->user(), $data);

        return back()->with('success', 'Verified donation recorded.');
    }
}
