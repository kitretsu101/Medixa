<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold text-gray-900">Medixa Blood Connect</h2></x-slot>
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
        <nav aria-label="Blood Connect administration" class="flex flex-wrap gap-2 rounded-2xl border border-rose-200 bg-white p-3 text-sm font-semibold text-red-800 shadow-sm [&_a]:rounded-lg [&_a]:px-3 [&_a]:py-2 [&_a]:transition [&_a:hover]:bg-rose-100 [&_a:focus-visible]:outline-2 [&_a:focus-visible]:outline-red-600">
            <a href="{{ route('admin.blood-connect.index') }}">Overview</a>
            <a href="{{ route('admin.blood-connect.requests.index') }}">Requests</a>
            <a href="{{ route('admin.blood-connect.donors.index') }}">Donors</a>
            <a href="{{ route('admin.blood-connect.donations.index') }}">Donations</a>
        </nav>
        @include('blood-connect.partials.messages')
        {{ $slot }}
    </div>
</x-app-layout>

