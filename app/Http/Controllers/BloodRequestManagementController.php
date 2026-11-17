<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBloodRequest;
use App\Models\BloodRequest;
use App\Services\BloodMatchingService;
use App\Services\BloodRequestService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BloodRequestManagementController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['status' => ['nullable', Rule::in(['pending', 'active', 'rejected', 'fulfilled', 'expired'])]]);
        $query = BloodRequest::query();
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        return view('blood-connect.admin.requests', ['requests' => $query->withCount(['events as report_count' => fn (Builder $query): Builder => $query->where('action', 'reported')])->latest()->orderByDesc('id')->paginate(20)->withQueryString()]);
    }

    public function show(BloodRequest $bloodRequest, BloodMatchingService $matching): View
    {
        $bloodRequest->load(['events.user:id,name', 'interests.bloodDonor', 'donations.bloodDonor']);

        return view('blood-connect.admin.request', ['bloodRequest' => $bloodRequest, 'matches' => $bloodRequest->isActive() ? $matching->matches($bloodRequest)->paginate(10) : null]);
    }

    public function update(UpdateBloodRequest $request, BloodRequest $bloodRequest, BloodRequestService $service): RedirectResponse
    {
        $service->transition($bloodRequest, $request->user(), $request->validated('status'), $request->validated('notes'));

        return back()->with('success', 'Blood request status updated.');
    }

    public function approve(Request $request, BloodRequest $bloodRequest, BloodRequestService $service): RedirectResponse
    {
        return $this->change($request, $bloodRequest, $service, 'active');
    }

    public function reject(Request $request, BloodRequest $bloodRequest, BloodRequestService $service): RedirectResponse
    {
        return $this->change($request, $bloodRequest, $service, 'rejected');
    }

    public function fulfill(Request $request, BloodRequest $bloodRequest, BloodRequestService $service): RedirectResponse
    {
        return $this->change($request, $bloodRequest, $service, 'fulfilled');
    }

    private function change(Request $request, BloodRequest $bloodRequest, BloodRequestService $service, string $status): RedirectResponse
    {
        $data = $request->validate(['notes' => ['required', 'string', 'min:5', 'max:2000']]);
        $service->transition($bloodRequest, $request->user(), $status, $data['notes']);

        return back()->with('success','Blood request status updated.');
    }
}
