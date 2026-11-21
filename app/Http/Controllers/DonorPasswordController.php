<?php

namespace App\Http\Controllers;

use App\Models\BloodDonor;
use App\Services\DonorIdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonorPasswordController extends Controller
{
    public function edit(BloodDonor $bloodDonor, string $token): View
    {
        return view('blood-connect.independent.reset-password', ['donorId' => $bloodDonor->id, 'token' => $token]);
    }

    public function update(Request $request, BloodDonor $bloodDonor, DonorIdentityService $identity): RedirectResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:64'], 'password' => ['required', 'string', 'min:8', 'max:72', 'regex:/\A[^\x00]{0,72}\z/', 'confirmed']], ['password.regex' => 'The password must not exceed 72 bytes or contain null characters.']);
        $identity->resetPassword($bloodDonor, $data['token'], $data['password']);

        return redirect()->route('blood-connect.donor.login')->with('success', 'Your donor password has been reset. Sign in with your donor phone number.');
    }
}
