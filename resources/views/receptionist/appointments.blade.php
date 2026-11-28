<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold text-slate-900">Hospital appointment requests</h2></x-slot>
    <div class="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">
        <p class="text-sm text-slate-600">{{ auth()->user()->hospital?->name }}</p>
        @if(session('status'))<p class="rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-800">{{ session('status') }}</p>@endif
        @error('appointment')<p class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $message }}</p>@enderror
        @include('receptionist.partials.booking-filters')
        @include('receptionist.partials.appointment-requests')
        {{ $requests->links() }}
    </div>
</x-app-layout>
