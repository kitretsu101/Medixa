<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloodRequest;
use App\Models\BloodRequest;
use App\Models\User;
use App\Services\BloodRequestService;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BloodRequestController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'blood_group' => ['nullable', Rule::in(BloodRequest::GROUPS)],
            'district' => ['nullable', 'string', 'max:100'], 'area' => ['nullable', 'string', 'max:255'],
            'hospital' => ['nullable', 'string', 'max:255'], 'q' => ['nullable', 'string', 'max:100'],
            'emergency_level' => ['nullable', Rule::in(BloodRequest::LEVELS)],
            'required_date' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', Rule::in(['newest', 'needed_soon', 'priority'])],
        ]);
        $query = BloodRequest::active();
        foreach (['blood_group', 'district', 'emergency_level', 'required_date'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        foreach (['area' => 'area', 'hospital' => 'hospital_name'] as $field => $column) {
            if (! empty($filters[$field])) {
                $query->where($column, 'like', '%'.$filters[$field].'%');
            }
        }
        if (! empty($filters['q'])) {
            $query->where(function (Builder $query) use ($filters): void {
                foreach (['request_code', 'hospital_name', 'district', 'area'] as $field) {
                    $query->orWhere($field, 'like', '%'.$filters['q'].'%');
                }
            });
        }
        match ($filters['sort'] ?? 'newest') {
            'needed_soon' => $query->orderBy('required_date'),
            'priority' => $query->orderByRaw("CASE emergency_level WHEN 'critical' THEN 0 WHEN 'urgent' THEN 1 ELSE 2 END"),
            default => $query->latest(),
        };

        return view('blood-connect.requests.index', ['requests' => $query->orderByDesc('id')->paginate(12)->withQueryString(), 'groups' => BloodRequest::GROUPS]);
    }

    public function create(): View
    {
        return view('blood-connect.requests.create', ['groups' => BloodRequest::GROUPS]);
    }

    public function sent(): View
    {
        return view('blood-connect.requests.sent');
    }

    public function store(StoreBloodRequest $request, BloodRequestService $service): RedirectResponse
    {
        $bloodRequest = $service->create($request->validated(), $request->user());

        return redirect()->route('blood-connect.sent')->with('request_code', $bloodRequest->request_code);
    }

    public function show(BloodRequest $bloodRequest): View
    {
        abort_unless($bloodRequest->isActive(), 404);

        return view('blood-connect.requests.show', compact('bloodRequest'));
    }

    public function report(Request $request, BloodRequest $bloodRequest, NotificationService $notifications): RedirectResponse
    {
        abort_unless($bloodRequest->isActive(), 404);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000'], 'website' => ['nullable', 'max:0']]);
        $bloodRequest->events()->create(['user_id' => $request->user()?->id, 'action' => 'reported', 'notes' => $data['reason']]);
        foreach (User::where('role', 'admin')->cursor() as $admin) {
            $notifications->alert($admin, 'Blood request reported', $bloodRequest->request_code.' was reported for review.', route('admin.blood-connect.requests.show', $bloodRequest), 'warning');
        }

        return back()->with('success','Your report has been sent to administrators for review.');
    }
}
