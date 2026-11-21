<?php

namespace App\Http\Controllers;

use App\Models\BloodDonor;
use App\Models\DonorContactRequest;
use App\Services\DonorContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DonorPortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $donor = $request->user('donor');

        return view('blood-connect.independent.dashboard', ['donor' => $donor, 'pendingCount' => $donor->contactRequests()->where('status', 'pending')->count()]);
    }

    public function preferences(Request $request): RedirectResponse
    {
        $data = $request->validate(['availability_status' => ['required', Rule::in(BloodDonor::AVAILABILITY)], 'public_profile' => ['required', 'boolean']]);
        $request->user('donor')->update($data);

        return back()->with('success', 'Your directory preferences have been saved.');
    }

    public function requests(Request $request): View
    {
        $data = $request->validate(['status' => ['nullable', Rule::in(DonorContactRequest::STATUSES)]]);
        $query = $request->user('donor')->contactRequests();
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        return view('blood-connect.independent.inbox', ['contactRequests' => $query->latest()->orderByDesc('id')->paginate(15)->withQueryString()]);
    }

    public function status(Request $request, int $contactRequest, DonorContactService $contacts): RedirectResponse
    {
        $request->user('donor')->contactRequests()->whereKey($contactRequest)->firstOrFail();
        $data = $request->validate(['status' => ['required', Rule::in(DonorContactRequest::STATUSES)]]);
        $contacts->transition($request->user('donor'), $contactRequest, $data['status']);

        return back()->with('success', 'Request status updated.');
    }
}
