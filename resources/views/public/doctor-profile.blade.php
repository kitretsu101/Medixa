@extends('layouts.public')

@section('title', 'Dr. ' . $doctor->name . ' — Medixa')
@section('meta_description', 'View the profile of Dr. ' . $doctor->name . ', ' . ($doctor->doctorProfile?->designation ?? 'Specialist') . ' at Medixa.')

@section('content')

{{-- ── Breadcrumb ───────────────────────────────────────────────── --}}
<nav class="bg-[#161b22] border-b border-[#21262d] py-3">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <ol class="flex items-center gap-2 text-sm text-[#656d76]">
            <li><a href="{{ route('home') }}" class="hover:text-[#22a08c] transition-colors duration-200">Home</a></li>
            <li class="text-[#3d444d]">/</li>
            <li><a href="{{ route('doctors.index') }}" class="hover:text-[#22a08c] transition-colors duration-200">Doctors</a></li>
            <li class="text-[#3d444d]">/</li>
            <li class="text-[#9198a1]">Dr. {{ $doctor->name }}</li>
        </ol>
    </div>
</nav>

{{-- ── Profile Hero ─────────────────────────────────────────────── --}}
<section class="bg-[#0d1117] py-16">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl border border-[#21262d] bg-[#161b22] p-8 sm:p-10">

            {{-- Background glow --}}
            <div class="pointer-events-none absolute -top-20 -right-20 h-80 w-80 rounded-full bg-[#22a08c]/8 blur-[80px]"></div>

            <div class="relative flex flex-col sm:flex-row gap-8 items-start">

                {{-- Avatar --}}
                <div class="flex-shrink-0">
                    <div class="h-24 w-24 sm:h-32 sm:w-32 rounded-3xl bg-gradient-to-br from-[#22a08c] to-[#16524a] flex items-center justify-center text-white text-4xl font-bold font-display shadow-2xl shadow-[#22a08c]/25">
                        {{ strtoupper(substr($doctor->name, 0, 1)) }}
                    </div>
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h1 class="font-display text-3xl sm:text-4xl font-bold text-white">Dr. {{ $doctor->name }}</h1>
                            @if($doctor->doctorProfile)
                            <p class="mt-1 text-lg text-[#9198a1]">{{ $doctor->doctorProfile->designation }}</p>
                            @endif
                        </div>
                        @if($doctor->doctorProfile?->department)
                        <a href="{{ route('departments.show', $doctor->doctorProfile->department) }}"
                           class="inline-flex items-center gap-1.5 rounded-full bg-[#22a08c]/15 border border-[#22a08c]/30 px-4 py-1.5 text-sm font-semibold text-[#22a08c] hover:bg-[#22a08c]/25 transition-colors duration-200">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            {{ $doctor->doctorProfile->department->name }}
                        </a>
                        @endif
                    </div>

                    @if($doctor->doctorProfile)
                    {{-- Stats --}}
                    <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div class="rounded-xl bg-[#21262d] p-4">
                            <div class="text-2xl font-bold font-display text-[#22a08c]">
                                {{ number_format($doctor->doctorProfile->patients_seen_count) }}
                            </div>
                            <div class="text-xs text-[#656d76] mt-0.5">Patients Seen</div>
                        </div>
                        <div class="rounded-xl bg-[#21262d] p-4">
                            <div class="text-2xl font-bold font-display text-white">OPD</div>
                            <div class="text-xs text-[#656d76] mt-0.5">Available</div>
                        </div>
                        <div class="rounded-xl bg-[#21262d] p-4 col-span-2 sm:col-span-1">
                            <div class="text-sm font-semibold text-white truncate">{{ $doctor->doctorProfile->qualification }}</div>
                            <div class="text-xs text-[#656d76] mt-0.5">Qualification</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Book CTA --}}
            <div class="relative mt-8 pt-6 border-t border-[#21262d] flex flex-col sm:flex-row gap-3">
                <a href="#"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#22a08c] px-6 py-3 text-sm font-semibold text-white hover:bg-[#188071] hover:shadow-lg hover:shadow-[#22a08c]/25 transition-all duration-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Book an Appointment
                </a>
                <a href="{{ route('doctors.index') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#30363d] px-6 py-3 text-sm font-medium text-[#9198a1] hover:border-[#3d444d] hover:text-white transition-all duration-200">
                    ← Back to Doctors
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ── Department Info ──────────────────────────────────────────── --}}
@if($doctor->doctorProfile?->department)
<section class="bg-[#0d1117] pb-16">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-[#21262d] bg-[#161b22] p-6">
            <h2 class="font-display text-lg font-semibold text-white mb-2">About the Department</h2>
            <p class="text-sm text-[#9198a1] leading-relaxed">
                {{ $doctor->doctorProfile->department->description ?? 'No description available for this department.' }}
            </p>
            <a href="{{ route('departments.show', $doctor->doctorProfile->department) }}"
               class="mt-4 inline-flex items-center gap-1.5 text-sm text-[#22a08c] font-medium hover:underline">
                View full department →
            </a>
        </div>
    </div>
</section>
@endif

@endsection
