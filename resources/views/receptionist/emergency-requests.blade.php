<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">Emergency Requests</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 space-y-4">
            @if(session('status'))
                <div class="rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-800">{{ session('status') }}</div>
            @endif
            @forelse($emergencyRequests as $emergencyRequest)
                <article class="rounded-2xl border p-5 {{ $emergencyRequest->status === 'pending' ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white' }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-bold text-slate-900">Request #{{ $emergencyRequest->id }} — {{ $emergencyRequest->patient_name ?? 'Name not provided' }}</h3>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-bold uppercase">{{ $emergencyRequest->status }}</span>
                    </div>
                    <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                        <div><dt class="font-semibold">Contact phone</dt><dd>{{ $emergencyRequest->contact_phone }}</dd></div>
                        <div><dt class="font-semibold">Location</dt><dd>{{ $emergencyRequest->location }}</dd></div>
                        <div><dt class="font-semibold">Issue type</dt><dd>{{ ucfirst($emergencyRequest->issue_type) }}</dd></div>
                        <div><dt class="font-semibold">Received</dt><dd>{{ $emergencyRequest->created_at->format('M d, Y H:i') }}</dd></div>
                        @if($emergencyRequest->handledBy)
                            <div><dt class="font-semibold">Handled by</dt><dd>{{ $emergencyRequest->handledBy->name }}</dd></div>
                        @endif
                    </dl>
                    <form method="POST" action="{{ route('receptionist.emergency-requests.update-status', $emergencyRequest) }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label for="status-{{ $emergencyRequest->id }}" class="block text-sm font-semibold">Status</label>
                            <select id="status-{{ $emergencyRequest->id }}" name="status" required class="mt-1 rounded-lg border-slate-300 text-sm">
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" @selected($emergencyRequest->status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            @error('status')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="note-{{ $emergencyRequest->id }}" class="block text-sm font-semibold">Note (optional)</label>
                            <textarea id="note-{{ $emergencyRequest->id }}" name="note" rows="2" maxlength="2000" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">{{ $emergencyRequest->note }}</textarea>
                            @error('note')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Update request</button>
                    </form>
                </article>
            @empty
                <p class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500">No emergency requests have been received.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
