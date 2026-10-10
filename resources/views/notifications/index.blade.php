<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Notifications</h2>
            <p class="text-sm text-slate-500">Appointments, queue calls, lab reports, prescriptions, and assistance replies.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 space-y-3">
            @forelse($notifications as $notification)
                @php
                    $level = $notification->data['level'] ?? 'info';
                    $levelClass = match ($level) {
                        'urgent' => 'border-rose-300 bg-rose-50',
                        'warning' => 'border-amber-300 bg-amber-50',
                        default => $notification->read_at ? 'border-slate-100 bg-white' : 'border-teal-200 bg-white',
                    };
                @endphp
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="rounded-2xl border shadow-sm p-4 {{ $levelClass }}">
                    @csrf
                    <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500">{{ $level }}</p>
                    <p class="font-semibold text-slate-900">{{ $notification->data['title'] ?? 'Update' }}</p>
                    <p class="text-sm text-slate-600 mt-1">{{ $notification->data['body'] ?? '' }}</p>
                    <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                        <span>{{ $notification->created_at?->diffForHumans() }}</span>
                        <button type="submit" class="font-semibold text-teal-700">{{ $notification->read_at ? 'Open' : 'Mark read' }}</button>
                    </div>
                </form>
            @empty
                <div class="bg-white rounded-2xl border border-slate-100 p-10 text-center text-slate-400">You're all caught up.</div>
            @endforelse
            {{ $notifications->links() }}
        </div>
    </div>
</x-app-layout>
