<?php

namespace App\Http\Controllers;

use App\Models\EmergencyRequest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmergencyRequestController extends Controller
{
    public function create(): View
    {
        return view('public.emergency', ['issueTypes' => EmergencyRequest::ISSUE_TYPES]);
    }

    public function store(Request $request, NotificationService $notifications): RedirectResponse
    {
        $validated = $request->validate([
            'patient_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'location' => ['required', 'string', 'max:255'],
            'issue_type' => ['required', Rule::in(EmergencyRequest::ISSUE_TYPES)],
        ]);

        DB::transaction(function () use ($validated, $notifications): void {
            $emergencyRequest = EmergencyRequest::create($validated);

            foreach (User::query()->where('role', 'receptionist')->get() as $receptionist) {
                $notifications->alert(
                    $receptionist,
                    'New emergency request',
                    'Emergency request #'.$emergencyRequest->id.' is awaiting review.',
                    route('receptionist.emergency-requests.index'),
                    'warning',
                );
            }
        });

        return redirect()->route('emergency.sent');
    }

    public function sent(): View
    {
        return view('public.emergency-sent');
    }
}
