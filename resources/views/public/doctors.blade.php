@extends('layouts.public')

@section('title', 'Find a Doctor — Medixa')
@section('meta_description', 'Browse our directory of specialist doctors. Filter by department to find the right consultant for your needs.')

@section('content')

{{-- ── Page Header ──────────────────────────────────────────────── --}}
<section class="bg-[#161b22] border-b border-[#21262d] py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-widest text-[#22a08c] mb-2">Our Team</p>
        <h1 class="font-display text-3xl sm:text-4xl font-bold text-white">Find a Doctor</h1>
        <p class="mt-2 text-[#656d76]">
            {{ $doctors->count() }} specialist{{ $doctors->count() !== 1 ? 's' : '' }} available
            @if($selectedDepartmentId)
                in this department
            @endif
        </p>
    </div>
</section>

{{-- ── Filters + Grid ───────────────────────────────────────────── --}}
<section class="bg-[#0d1117] py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        {{-- Department Filter Tabs --}}
        <div class="flex flex-wrap gap-2 mb-10">
            <a href="{{ route('doctors.index') }}"
               id="filter-all"
               class="rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200
                      {{ is_null($selectedDepartmentId) ? 'bg-[#22a08c] text-white shadow-lg shadow-[#22a08c]/25' : 'border border-[#30363d] text-[#9198a1] hover:border-[#3d444d] hover:text-white' }}">
                All Departments
            </a>
            @foreach($departments as $dept)
            <a href="{{ route('doctors.index', ['department' => $dept->id]) }}"
               id="filter-dept-{{ $dept->id }}"
               class="rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200
                      {{ $selectedDepartmentId === $dept->id ? 'bg-[#22a08c] text-white shadow-lg shadow-[#22a08c]/25' : 'border border-[#30363d] text-[#9198a1] hover:border-[#3d444d] hover:text-white' }}">
                {{ $dept->name }}
            </a>
            @endforeach
        </div>

        {{-- Doctor Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($doctors as $doctor)
            <a href="{{ route('doctors.show', $doctor) }}"
               id="doctor-card-{{ $doctor->id }}"
               class="group relative flex flex-col rounded-2xl border border-[#21262d] bg-[#161b22] p-6 hover:border-[#22a08c]/40 hover:bg-[#21262d] transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#22a08c]/10">

                {{-- Avatar --}}
                <div class="flex items-start gap-4 mb-4">
                    <div class="relative flex-shrink-0">
                        <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-[#22a08c] to-[#188071] flex items-center justify-center text-white text-xl font-bold font-display shadow-lg group-hover:shadow-[#22a08c]/40 transition-shadow duration-300">
                            {{ strtoupper(substr($doctor->name, 0, 1)) }}
                        </div>
                        <div class="absolute -bottom-1 -right-1 h-4 w-4 rounded-full bg-[#22a08c] border-2 border-[#161b22]"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h2 class="font-display text-base font-semibold text-white group-hover:text-[#22a08c] transition-colors duration-200 truncate">
                            Dr. {{ $doctor->name }}
                        </h2>
                        @if($doctor->doctorProfile)
                        <p class="text-sm text-[#9198a1] truncate">{{ $doctor->doctorProfile->designation }}</p>
                        <p class="text-xs text-[#22a08c] font-medium truncate">
                            {{ $doctor->doctorProfile->department->name ?? '—' }}
                        </p>
                        @endif
                    </div>
                </div>

                @if($doctor->doctorProfile)
                <div class="mt-auto pt-4 border-t border-[#21262d]">
                    <p class="text-xs text-[#656d76] truncate">{{ $doctor->doctorProfile->qualification }}</p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-xs text-[#9198a1]">
                            {{ number_format($doctor->doctorProfile->patients_seen_count) }} patients seen
                        </span>
                        <svg class="h-4 w-4 text-[#3d444d] group-hover:text-[#22a08c] group-hover:translate-x-1 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </div>
                </div>
                @endif

                <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-[#22a08c]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            </a>
            @empty
            <div class="col-span-full py-20 text-center">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-[#161b22] border border-[#21262d] mb-4">
                    <svg class="h-8 w-8 text-[#3d444d]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <p class="text-[#656d76]">No doctors found for this department.</p>
            </div>
            @endforelse
        </div>
    </div>
</section>

@endsection
