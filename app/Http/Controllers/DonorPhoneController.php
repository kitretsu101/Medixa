<?php

namespace App\Http\Controllers;

use App\Services\DonorIdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DonorPhoneController extends Controller
{
    public function verify(Request $request, DonorIdentityService $identity): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'regex:/^[0-9]{6}$/']]);
        $identity->verifyOtp($request->user('donor'), $data['code']);

        return redirect()->route('blood-connect.donor.dashboard')->with('success', 'Your phone has been verified.');
    }

    public function resend(Request $request, DonorIdentityService $identity): RedirectResponse
    {
        $code = $identity->sendOtp($request->user('donor'));

        return back()->with('success', 'Verification requested. SMS delivery is currently a development stub.')->with('dev_otp', $code);
    }
}
