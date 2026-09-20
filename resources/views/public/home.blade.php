@extends('layouts.public')

@section('title', 'Medixa — Smart Hospital OPD')
@section('meta_description', 'Welcome to Medixa. Find specialist doctors, browse departments, and book OPD appointments online.')

@section('content')

{{-- ── Hero Section ────────────────────────────────────────────── --}}
<section class="relative overflow-hidden bg-[#0d1117] pt-20 pb-32">

    {{-- Animated gradient blobs --}}
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -top-40 -left-20 h-[600px] w-[600px] rounded-full bg-[#22a08c]/10 blur-[120px] animate-pulse"></div>
        <div class="absolute -bottom-20 right-0 h-[500px] w-[500px] rounded-full bg-[#188071]/10 blur-[100px] animate-pulse" style="animation-delay:1s"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[300px] w-[300px] rounded-full bg-[#f0883e]/5 blur-[80px] animate-pulse" style="animation-delay:2s"></div>
    </div>

    {{-- Grid pattern overlay --}}
    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(33,38,45,0.4)_1px,transparent_1px),linear-gradient(90deg,rgba(33,38,45,0.4)_1px,transparent_1px)] bg-[size:60px_60px]"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">

        {{-- Badge --}}
        <div class="inline-flex items-center gap-2 rounded-full border border-[#22a08c]/30 bg-[#22a08c]/10 px-4 py-1.5 mb-8">
            <span class="h-2 w-2 rounded-full bg-[#22a08c] animate-ping"></span>
            <span class="text-xs font-semibold text-[#22a08c] tracking-wider uppercase">Smart OPD System — Now Live</span>
        </div>

        <h1 class="font-display text-5xl sm:text-6xl lg:text-7xl font-bold text-white leading-tight tracking-tight">
            Your Health,<br>
            <span class="bg-gradient-to-r from-[#22a08c] via-[#3ebba6] to-[#f0883e] bg-clip-text text-transparent">
                Intelligently Managed
            </span>
        </h1>

        <p class="mx-auto mt-6 max-w-2xl text-lg text-[#9198a1] leading-relaxed">
            Medixa connects patients with specialist doctors across departments, streamlines OPD queues in real-time, and keeps your medical records secure with consent-scoped access.
        </p>

        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('doctors.index') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-[#22a08c] px-8 py-3.5 text-base font-semibold text-white shadow-xl shadow-[#22a08c]/25 hover:bg-[#188071] hover:shadow-[#22a08c]/50 hover:-translate-y-0.5 transition-all duration-200">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Find a Doctor
            </a>
            <a href="#departments"
               class="inline-flex items-center gap-2 rounded-xl border border-[#30363d] bg-[#161b22] px-8 py-3.5 text-base font-semibold text-[#b9bfc8] hover:border-[#3d444d] hover:text-white hover:-translate-y-0.5 transition-all duration-200">
                View Departments
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </a>
        </div>

        {{-- Stats strip --}}
        <div class="mt-16 grid grid-cols-3 gap-6 sm:gap-8 max-w-lg mx-auto">
            @foreach([['24/7', 'Emergency Care'], ['500+', 'Patients Served'], ['15+', 'Specialists']] as [$num, $label])
            <div class="text-center">
                <div class="font-display text-2xl sm:text-3xl font-bold text-white">{{ $num }}</div>
                <div class="text-xs text-[#656d76] mt-1">{{ $label }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Departments Section ─────────────────────────────────────── --}}
<section id="departments" class="bg-[#0d1117] py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-14">
            <p class="text-xs font-semibold uppercase tracking-widest text-[#22a08c] mb-2">Our Specialities</p>
            <h2 class="font-display text-3xl sm:text-4xl font-bold text-white">Medical Departments</h2>
            <p class="mt-3 text-[#656d76] max-w-xl mx-auto">World-class care across a broad spectrum of medical specialities, staffed by experienced consultants.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($departments as $department)
            <a href="{{ route('departments.show', $department) }}"
               class="group relative overflow-hidden rounded-2xl border border-[#21262d] bg-[#161b22] p-6 hover:border-[#22a08c]/40 hover:bg-[#21262d] transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#22a08c]/10">

                {{-- Icon --}}
                <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-[#22a08c]/10 text-[#22a08c] group-hover:bg-[#22a08c]/20 transition-colors duration-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>

                <h3 class="font-display text-lg font-semibold text-white group-hover:text-[#22a08c] transition-colors duration-200">
                    {{ $department->name }}
                </h3>

                @if($department->description)
                <p class="mt-2 text-sm text-[#656d76] line-clamp-2 leading-relaxed">{{ $department->description }}</p>
                @endif

                <div class="mt-4 flex items-center justify-between">
                    <span class="text-xs text-[#9198a1]">
                        {{ $department->doctor_profiles_count }}
                        {{ Str::plural('specialist', $department->doctor_profiles_count) }}
                    </span>
                    <svg class="h-4 w-4 text-[#3d444d] group-hover:text-[#22a08c] group-hover:translate-x-1 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </div>

                {{-- Hover glow --}}
                <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-[#22a08c]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            </a>
            @empty
            <div class="col-span-full text-center py-12 text-[#656d76]">
                No departments available yet.
            </div>
            @endforelse
        </div>
    </div>
</section>

{{-- ── Why Medixa section ──────────────────────────────────────── --}}
<section class="bg-[#161b22] py-24 border-t border-[#21262d]">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <p class="text-xs font-semibold uppercase tracking-widest text-[#22a08c] mb-2">Why Choose Us</p>
            <h2 class="font-display text-3xl sm:text-4xl font-bold text-white">Healthcare, Reimagined</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach([
                ['🏥', 'Live OPD Queues', 'See real-time queue status before you arrive. No more waiting in the dark.'],
                ['🔒', 'Consent-Scoped Data', 'Your records are only shared with explicit, time-limited permission.'],
                ['📋', 'Digital Prescriptions', 'Receive and manage prescriptions digitally — always accessible, never lost.'],
                ['🧪', 'Lab Report Delivery', 'Lab reports uploaded directly to your secure patient portal.'],
            ] as [$icon, $title, $desc])
            <div class="rounded-2xl border border-[#21262d] bg-[#0d1117] p-6 hover:border-[#22a08c]/30 transition-all duration-300">
                <div class="text-3xl mb-4">{{ $icon }}</div>
                <h3 class="font-display font-semibold text-white mb-2">{{ $title }}</h3>
                <p class="text-sm text-[#656d76] leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
