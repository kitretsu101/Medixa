<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold text-gray-900">Medixa Blood Connect</h2></x-slot>
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
        <nav aria-label="Blood Connect administration" class="flex flex-wrap gap-4 text-sm font-semibold text-red-800">
            <a href="{{ route('admin.blood-connect.index') }}">Overview</a>
            <a href="{{ route('admin.blood-connect.requests.index') }}">Requests</a>
            <a href="{{ route('admin.blood-connect.donors.index') }}">Donors</a>
            <a href="{{ route('admin.blood-connect.donations.index') }}">Donations</a>
        </nav>
        @include('blood-connect.partials.messages')
        {{ $slot }}
    </div>
</x-app-layout>

