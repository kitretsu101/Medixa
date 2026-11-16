<?php

namespace App\Http\Controllers;

use App\Models\EmergencyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReceptionistEmergencyRequestController extends Controller
{
    public function index(): View
    {
        $emergencyRequests = EmergencyRequest::query()
            ->with('handledBy:id,name')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->orderByDesc('id')
            ->get();

        return view('receptionist.emergency-requests', [
            'emergencyRequests' => $emergencyRequests,
            'statuses' => EmergencyRequest::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, EmergencyRequest $emergencyRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(EmergencyRequest::STATUSES)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $emergencyRequest->update([
            ...$validated,
            'handled_by' => $request->user()->id,
        ]);

        return redirect()->route('receptionist.emergency-requests.index')
            ->with('status', 'Emergency request updated.');
    }
}
