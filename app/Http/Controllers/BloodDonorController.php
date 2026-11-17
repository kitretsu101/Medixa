<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterBloodDonor;
use App\Models\BloodDonor;
use App\Models\BloodRequest;
use App\Models\User;
use App\Services\BloodMatchingService;
use App\Services\BloodRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BloodDonorController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (BloodDonor::where('user_id', $request->user()->id)->exists()) {
            return redirect()->route('blood-connect.donor.profile');
        }

        return view('blood-connect.donor.form', ['donor' => new BloodDonor, 'groups' => BloodRequest::GROUPS]);
    }

    public function store(RegisterBloodDonor $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (BloodDonor::where('user_id', $request->user()->id)->exists()) {
                throw ValidationException::withMessages(['full_name' => 'This account already has a donor profile.']);
            }
            BloodDonor::create([...$request->validated(), 'user_id' => $request->user()->id]);
        });

        return redirect()->route('blood-connect.donor.profile')->with('success', 'Your donor profile has been saved.');
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $donor = BloodDonor::where('user_id', $request->user()->id)->first();
        if (! $donor) {
            return redirect()->route('blood-connect.donor.register');
        }
        Gate::authorize('update', $donor);

        return view('blood-connect.donor.profile', ['donor' => $donor, 'groups' => BloodRequest::GROUPS, 'donations' => $donor->donations()->with('bloodRequest')->latest()->paginate(10)]);
    }

    public function update(RegisterBloodDonor $request): RedirectResponse
    {
        $donor = BloodDonor::where('user_id', $request->user()->id)->firstOrFail();
        Gate::authorize('update', $donor);
        $data = $request->validated();
        if ($donor->phone !== $data['phone']) {
            $donor->phone_verified_at = null;
        }
        $donor->fill($data)->save();

        return back()->with('success', 'Your donor profile has been saved.');
    }

    public function availability(Request $request): RedirectResponse
    {
        $donor = BloodDonor::where('user_id', $request->user()->id)->firstOrFail();
        Gate::authorize('update', $donor);
        $data = $request->validate(['availability_status' => ['required', Rule::in(BloodDonor::AVAILABILITY)]]);
        $donor->update($data);

        return back()->with('success', 'Availability updated.');
    }

    public function interest(Request $request, BloodRequest $bloodRequest, BloodRequestService $service, BloodMatchingService $matching): RedirectResponse
    {
        abort_unless($bloodRequest->isActive(), 404);
        $donor = BloodDonor::where('user_id', $request->user()->id)->first();
        if (! $donor) {
            return redirect()->route('blood-connect.donor.register');
        }
        Gate::authorize('update', $donor);
        if (! in_array($donor->blood_group, $matching->compatibleDonorGroups($bloodRequest->blood_group), true)) {
            throw ValidationException::withMessages(['donor' => 'Your registered blood group is not a preliminary red-cell match for this request.']);
        }
        $service->interest($bloodRequest, $donor);

        return back()->with('success', 'Your interest has been recorded. This does not confirm a donation or fulfill the request.');
    }
}
