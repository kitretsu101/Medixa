<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Admin Portal') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Hospital administration and compliance overview</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                    <span class="w-2 h-2 mr-1.5 bg-indigo-500 rounded-full"></span> Admin Access
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Patients</p>
                    <h3 class="text-3xl font-extrabold text-gray-900 mt-1">{{ $stats['total_patients'] }}</h3>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Doctors</p>
                    <h3 class="text-3xl font-extrabold text-indigo-600 mt-1">{{ $stats['total_doctors'] }}</h3>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Departments</p>
                    <h3 class="text-3xl font-extrabold text-teal-600 mt-1">{{ $stats['total_departments'] }}</h3>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Appointments</p>
                    <h3 class="text-3xl font-extrabold text-amber-600 mt-1">{{ $stats['total_appointments'] }}</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">Pending Appointments</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['pending_appointments'] }}</p>
                </div>
                <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">Completed Appointments</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['completed_appointments'] }}</p>
                </div>
                <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">Lab Reports</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $stats['total_lab_reports'] }}</p>
                </div>
                <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">Pending Reviews</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['pending_reviews'] }}</p>
                </div>
            </div>

            <!-- Quick Navigation -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5">
                <a href="{{ route('admin.departments.index') }}" class="group block p-6 bg-gradient-to-br from-indigo-900 to-indigo-700 rounded-2xl text-white shadow-md hover:shadow-xl transition transform hover:-translate-y-0.5">
                    <h4 class="text-lg font-bold mb-1">Departments</h4>
                    <p class="text-xs text-indigo-200">Manage hospital departments</p>
                </a>
                <a href="{{ route('admin.doctors.index') }}" class="group block p-6 bg-gradient-to-br from-teal-900 to-teal-700 rounded-2xl text-white shadow-md hover:shadow-xl transition transform hover:-translate-y-0.5">
                    <h4 class="text-lg font-bold mb-1">Doctors</h4>
                    <p class="text-xs text-teal-200">Register and manage doctors</p>
                </a>
                <a href="{{ route('admin.schedule') }}" class="group block p-6 bg-gradient-to-br from-amber-900 to-amber-700 rounded-2xl text-white shadow-md hover:shadow-xl transition transform hover:-translate-y-0.5">
                    <h4 class="text-lg font-bold mb-1">Schedule</h4>
                    <p class="text-xs text-amber-200">View doctor schedules & queues</p>
                </a>
                <a href="{{ route('admin.analytics') }}" class="group block p-6 bg-gradient-to-br from-sky-900 to-sky-700 rounded-2xl text-white shadow-md hover:shadow-xl transition transform hover:-translate-y-0.5">
                    <h4 class="text-lg font-bold mb-1">Analytics</h4>
                    <p class="text-xs text-sky-200">Waits, load, and queue comparison</p>
                </a>
                <a href="{{ route('admin.audit-oversight') }}" class="group block p-6 bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl text-white shadow-md hover:shadow-xl transition transform hover:-translate-y-0.5">
                    <h4 class="text-lg font-bold mb-1">Audit Oversight</h4>
                    <p class="text-xs text-slate-300">Aggregate compliance reports</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
