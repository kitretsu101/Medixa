<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Assistance requests</h2>
            <p class="text-sm text-slate-500">Non-emergency messages from patients linked to you. This is not an emergency service.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 space-y-4">
            @if(session('success'))
                <div class="rounded-xl bg-teal-50 border border-teal-200 p-4 text-sm text-teal-800">{{ session('success') }}</div>
            @endif

            @forelse($requests as $item)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $item->patient?->name }}</p>
                            <p class="text-xs text-slate-500">{{ $item->created_at?->format('M d, Y H:i') }}</p>
                        </div>
                        <span class="text-xs font-semibold uppercase tracking-wide px-2 py-1 rounded-full bg-slate-100 text-slate-700">{{ $item->status }}</span>
                    </div>
                    <p class="text-sm text-slate-700">{{ $item->message }}</p>
                    @if($item->response)
                        <p class="text-sm text-teal-800 bg-teal-50 rounded-lg p-3">{{ $item->response }}</p>
                    @else
                        <form method="POST" action="{{ route('doctor.assistance.respond', $item) }}" class="space-y-2">
                            @csrf
                            <textarea name="response" rows="3" class="w-full rounded-xl border-slate-300 text-sm" placeholder="Write a short reply" required>{{ old('response') }}</textarea>
                            @error('response')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">Send response</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-slate-100 p-10 text-center text-slate-400">No assistance requests.</div>
            @endforelse

            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>
