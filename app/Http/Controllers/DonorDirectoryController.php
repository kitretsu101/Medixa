<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDonorContactRequest;
use App\Models\BloodDonor;
use App\Models\BloodRequest;
use App\Services\DonorContactService;
use App\Support\BangladeshDistricts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DonorDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'blood_group' => ['nullable', Rule::in(BloodRequest::GROUPS)],
            'district' => ['nullable', Rule::in(BangladeshDistricts::ALL)],
            'area' => ['nullable', 'string', 'max:255'],
            'availability_status' => ['nullable', Rule::in(BloodDonor::AVAILABILITY)],
        ]);
        $query = BloodDonor::query()->whereNotNull('phone_verified_at')->where('public_profile', true)->where('availability_status', 'available');
        foreach (['blood_group', 'district', 'availability_status'] as $field) {
            if (! empty($data[$field])) {
                $query->where($field, $data[$field]);
            }
        }
        if (! empty($data['area'])) {
            $query->where('area', 'like', '%'.$data['area'].'%');
        }
        $donors = $query->select(['id', 'full_name', 'blood_group', 'district', 'area', 'availability_status'])->latest('id')->paginate(12)->withQueryString();

        return view('blood-connect.independent.directory', ['donors' => $donors, 'groups' => BloodRequest::GROUPS, 'districts' => BangladeshDistricts::ALL]);
    }

    public function create(BloodDonor $bloodDonor): View
    {
        abort_unless($bloodDonor->acceptsContactRequests(), 404);

        return view('blood-connect.independent.contact', [
            'donorCard' => $bloodDonor->only(['id', 'full_name', 'blood_group', 'district', 'area']),
            'groups' => BloodRequest::GROUPS,
            'districts' => BangladeshDistricts::ALL,
        ]);
    }

    public function store(StoreDonorContactRequest $request, BloodDonor $bloodDonor, DonorContactService $contacts): RedirectResponse
    {
        $contacts->create($bloodDonor, $request->validated());

        return redirect()->route('blood-connect.donors.sent')->with('success', 'Your request has been sent. The donor can contact you directly.');
    }

    public function sent(): View
    {
        return view('blood-connect.independent.sent');
    }
}
