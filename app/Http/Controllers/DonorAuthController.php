<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterIndependentDonor;
use App\Models\BloodRequest;
use App\Services\DonorIdentityService;
use App\Support\BangladeshDistricts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DonorAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('donor')->check()) {
            return redirect()->route('blood-connect.donor.dashboard');
        }

        return view('blood-connect.independent.register', ['groups' => BloodRequest::GROUPS, 'districts' => BangladeshDistricts::ALL]);
    }

    public function store(RegisterIndependentDonor $request, DonorIdentityService $identity): RedirectResponse
    {
        abort_if(Auth::guard('donor')->check(), 409);
        $donor = $identity->register($request->validated());
        Auth::guard('donor')->login($donor);
        $code = $identity->sendOtp($donor);

        return redirect()->route('blood-connect.donor.dashboard')->with('success', 'Your donor account has been created. Public listing follows your directory preferences and availability; phone verification is optional.')->with('dev_otp', $code);
    }

    public function login(): View|RedirectResponse
    {
        return Auth::guard('donor')->check()
            ? redirect()->route('blood-connect.donor.dashboard')
            : view('blood-connect.independent.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:20'], 'password' => ['required', 'string', 'max:72', 'regex:/\A[^\x00]{0,72}\z/'], 'remember' => ['sometimes', 'boolean']]);
        $phone = preg_replace('/^(?:\\+88|0088)/', '', trim($data['phone']));
        if (! Auth::guard('donor')->attempt(['phone' => $phone, 'password' => $data['password']], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['phone' => 'The donor phone number or password is incorrect.']);
        }

        return redirect()->route('blood-connect.donor.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('donor')->logout();
        Auth::guard('donor')->getSession()->invalidate();

        return redirect()->route('blood-connect.donor.login');
    }
}
