@extends('layouts.public')

@section('title', $department->name . ' Department — Medixa')
@section('meta_description', 'Learn about the ' . $department->name . ' department at Medixa and meet our specialist doctors.')

@section('content')

<nav class="border-b border-stone-200 py-3">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <ol class="flex items-center gap-2 text-sm text-stone-400">
            <li><a href="{{ route('home') }}" class="hover:text-[#3f7f72]">Home</a></li>
            <li>/</li>
            <li class="text-stone-600">{{ $department->name }}</li>
        </ol>
    </div>
</nav>

<section class="py-12">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mb-10 rounded-3xl border border-stone-200 bg-white p-8 shadow-sm sm:p-12">
            <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-[#3f7f72]">Department</p>
            <h1 class="font-display text-3xl font-semibold text-stone-800 sm:text-4xl">{{ $department->name }}</h1>
            @if($department->description)
                <p class="mt-3 max-w-2xl leading-relaxed text-stone-500">{{ $department->description }}</p>
            @endif
            <p class="mt-4 text-sm text-stone-500">
                {{ $department->doctorProfiles->count() }}
                {{ Str::plural('specialist', $department->doctorProfiles->count()) }}
            </p>
        </div>

        <h2 class="mb-6 font-display text-xl font-semibold text-stone-800">Specialists</h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($department->doctorProfiles as $profile)
                @if($profile->user)
                    <a href="{{ route('doctors.show', $profile->user) }}"
                       id="dept-doctor-{{ $profile->user_id }}"
                       class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-[#cfe6df]">
                        <div class="mb-4 flex items-start gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-[#eef7f4] font-semibold text-[#3f7f72]">
                                {{ strtoupper(substr($profile->user->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="truncate font-display font-semibold text-stone-800">
                                    <x-doctor-name :name="$profile->user->name" />
                                </h3>
                                <p class="truncate text-sm text-stone-500">{{ $profile->designation }}</p>
                            </div>
                        </div>
                        <p class="truncate text-xs text-stone-400">{{ $profile->qualification }}</p>
                        <p class="mt-3 text-xs text-stone-400">{{ number_format($profile->patients_seen_count) }} patients</p>
                    </a>
                @endif
            @empty
                <div class="col-span-full py-12 text-center text-stone-500">
                    No doctors are assigned to this department yet.
                </div>
            @endforelse
        </div>

        <a href="{{ route('doctors.index') }}" class="mt-10 inline-flex text-sm font-medium text-[#3f7f72] hover:underline">
            View all doctors
        </a>
    </div>
</section>

@endsection
