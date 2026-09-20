@extends('layouts.public')

@section('title', $department->name . ' Department — Medixa')
@section('meta_description', 'Learn about the ' . $department->name . ' department at Medixa and meet our specialist doctors.')

@section('content')

{{-- ── Breadcrumb ───────────────────────────────────────────────── --}}
<nav class="bg-[#161b22] border-b border-[#21262d] py-3">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <ol class="flex items-center gap-2 text-sm text-[#656d76]">
            <li><a href="{{ route('home') }}" class="hover:text-[#22a08c] transition-colors duration-200">Home</a></li>
            <li class="text-[#3d444d]">/</li>
            <li class="text-[#9198a1]">{{ $department->name }}</li>
        </ol>
    </div>
</nav>

{{-- ── Department Hero ──────────────────────────────────────────── --}}
<section class="bg-[#0d1117] py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="relative overflow-hidden rounded-3xl border border-[#21262d] bg-[#161b22] p-8 sm:p-12 mb-10">
            <div class="pointer-events-none absolute -top-24 -right-24 h-96 w-96 rounded-full bg-[#22a08c]/8 blur-[100px]"></div>

            <div class="relative flex flex-col sm:flex-row items-start gap-6">
                <div class="inline-flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-[#22a08c]/15 border border-[#22a08c]/30">
                    <svg class="h-8 w-8 text-[#22a08c]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#22a08c] mb-1">Department</p>
                    <h1 class="font-display text-3xl sm:text-4xl font-bold text-white">{{ $department->name }}</h1>
                    @if($department->description)
                    <p class="mt-3 max-w-2xl text-[#9198a1] leading-relaxed">{{ $department->description }}</p>
                    @endif
                    <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-[#21262d] px-4 py-1.5 text-sm text-[#9198a1]">
                        <svg class="h-4 w-4 text-[#22a08c]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ $department->doctorProfiles->count() }}
                        {{ Str::plural('specialist', $department->doctorProfiles->count()) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Doctors in this Department ────────────────────── --}}
        <h2 class="font-display text-xl font-bold text-white mb-6">Our Specialists</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($department->doctorProfiles as $profile)
            @if($profile->user)
            <a href="{{ route('doctors.show', $profile->user) }}"
               id="dept-doctor-{{ $profile->user_id }}"
               class="group relative flex flex-col rounded-2xl border border-[#21262d] bg-[#161b22] p-6 hover:border-[#22a08c]/40 hover:bg-[#21262d] transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#22a08c]/10">

                <div class="flex items-start gap-4 mb-4">
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-[#22a08c] to-[#188071] flex items-center justify-center text-white font-bold text-lg font-display shadow-md group-hover:shadow-[#22a08c]/40 transition-shadow duration-300 flex-shrink-0">
                        {{ strtoupper(substr($profile->user->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-display font-semibold text-white group-hover:text-[#22a08c] transition-colors duration-200 truncate">
                            Dr. {{ $profile->user->name }}
                        </h3>
                        <p class="text-sm text-[#9198a1] truncate">{{ $profile->designation }}</p>
                    </div>
                </div>

                <p class="text-xs text-[#656d76] truncate mb-3">{{ $profile->qualification }}</p>

                <div class="mt-auto flex items-center justify-between pt-3 border-t border-[#21262d]">
                    <span class="text-xs text-[#9198a1]">
                        {{ number_format($profile->patients_seen_count) }} patients
                    </span>
                    <svg class="h-4 w-4 text-[#3d444d] group-hover:text-[#22a08c] group-hover:translate-x-1 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </div>

                <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-[#22a08c]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            </a>
            @endif
            @empty
            <div class="col-span-full py-12 text-center text-[#656d76]">
                No doctors assigned to this department yet.
            </div>
            @endforelse
        </div>

        <div class="mt-10">
            <a href="{{ route('doctors.index') }}"
               class="inline-flex items-center gap-2 text-sm text-[#9198a1] hover:text-white transition-colors duration-200">
                ← View all doctors
            </a>
        </div>
    </div>
</section>

@endsection
