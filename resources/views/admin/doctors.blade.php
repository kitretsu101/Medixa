<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-900 leading-tight">
                    {{ __('Doctor Management') }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">Manage doctor credentials, clinical appointments, and departments</p>
            </div>
            <div>
                <a href="{{ route('admin.doctors.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Register New Doctor</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-sm flex items-center gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-4 px-6">Doctor Details</th>
                                <th class="py-4 px-6">Department</th>
                                <th class="py-4 px-6">Designation & Qualification</th>
                                <th class="py-4 px-6 text-center">Patients Seen</th>
                                <th class="py-4 px-6 text-center">Live Queue</th>
                                <th class="py-4 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @forelse($doctors as $doctor)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center font-bold text-base shrink-0">
                                                {{ strtoupper(substr($doctor->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-900">{{ $doctor->name }}</p>
                                                <p class="text-xs text-slate-500">{{ $doctor->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-800">
                                            {{ $doctor->doctorProfile?->department?->name ?? 'Unassigned' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-slate-600">
                                        <p class="font-semibold text-slate-800 text-xs">{{ $doctor->doctorProfile?->designation ?? 'Consultant' }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $doctor->doctorProfile?->qualification ?? 'MBBS' }}</p>
                                    </td>
                                    <td class="py-4 px-6 text-center font-semibold text-slate-700">
                                        {{ number_format($doctor->doctorProfile?->patients_seen_count ?? 0) }}
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        @if($doctor->liveQueue && $doctor->liveQueue->is_present)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 mr-1 bg-emerald-500 rounded-full animate-pulse"></span>
                                                Serving #{{ $doctor->liveQueue->current_token }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                                Offline
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.doctors.edit', $doctor) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('admin.doctors.destroy', $doctor) }}" onsubmit="return confirm('Are you sure you want to remove this doctor account?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-semibold transition">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-400">
                                        No doctors registered yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($doctors->hasPages())
                    <div class="p-4 border-t border-slate-100">
                        {{ $doctors->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
