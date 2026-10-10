@extends('layouts.public')

@section('title', \App\Support\DoctorName::format($doctor->name).' — Medixa')
@section('meta_description', 'View the profile of '.\App\Support\DoctorName::format($doctor->name).', '.($doctor->doctorProfile?->designation ?? 'Specialist').' at Medixa.')

@section('content')

<nav class="border-b border-stone-200 py-3">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <ol class="flex flex-wrap items-center gap-2 text-sm text-stone-400">
            <li><a href="{{ route('home') }}" class="hover:text-[#3f7f72]">Home</a></li>
            <li>/</li>
            <li><a href="{{ route('doctors.index') }}" class="hover:text-[#3f7f72]">Doctors</a></li>
            <li>/</li>
            <li class="text-stone-600"><x-doctor-name :name="$doctor->name" /></li>
        </ol>
    </div>
</nav>

<section class="py-12">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-stone-200 bg-white p-8 shadow-sm sm:p-10">
            <div class="flex flex-col items-start gap-8 sm:flex-row">
                <div class="flex h-24 w-24 flex-shrink-0 items-center justify-center rounded-3xl bg-[#eef7f4] text-4xl font-semibold text-[#3f7f72] sm:h-32 sm:w-32">
                    {{ strtoupper(substr($doctor->name, 0, 1)) }}
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h1 class="font-display text-3xl font-semibold text-stone-800 sm:text-4xl"><x-doctor-name :name="$doctor->name" /></h1>
                            @if($doctor->doctorProfile)
                                <p class="mt-1 text-lg text-stone-500">{{ $doctor->doctorProfile->designation }}</p>
                            @endif
                        </div>
                        @if($doctor->doctorProfile?->department)
                            <a href="{{ route('departments.show', $doctor->doctorProfile->department) }}"
                               class="rounded-full bg-[#eef7f4] px-4 py-1.5 text-sm font-semibold text-[#3f7f72]">
                                {{ $doctor->doctorProfile->department->name }}
                            </a>
                        @endif
                    </div>

                    @if($doctor->doctorProfile)
                        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl bg-[#f7f5f2] p-4">
                                <div class="font-display text-2xl font-semibold text-[#3f7f72]">
                                    {{ number_format($doctor->doctorProfile->patients_seen_count) }}
                                </div>
                                <div class="mt-0.5 text-xs text-stone-500">Patients seen</div>
                            </div>
                            <div class="rounded-2xl bg-[#f7f5f2] p-4">
                                <div class="font-display text-2xl font-semibold text-stone-800">OPD</div>
                                <div class="mt-0.5 text-xs text-stone-500">Available</div>
                            </div>
                            <div class="col-span-2 rounded-2xl bg-[#f7f5f2] p-4 sm:col-span-1">
                                <div class="truncate text-sm font-semibold text-stone-800">{{ $doctor->doctorProfile->qualification }}</div>
                                <div class="mt-0.5 text-xs text-stone-500">Qualification</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-3 border-t border-stone-100 pt-6 sm:flex-row">
                <a href="{{ route('patient.appointments.create', ['doctor' => $doctor->id]) }}"
                   class="inline-flex items-center justify-center rounded-full bg-[#5eaea0] px-6 py-3 text-sm font-semibold text-white hover:bg-[#4c9d90]">
                    Book an appointment
                </a>
                <a href="{{ route('doctors.index') }}"
                   class="inline-flex items-center justify-center rounded-full border border-stone-200 px-6 py-3 text-sm font-medium text-stone-600 hover:border-[#b7ddd4]">
                    Back to doctors
                </a>
            </div>
        </div>
    </div>
</section>

@if($doctor->doctorProfile?->department)
<section class="pb-16">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="mb-2 font-display text-lg font-semibold text-stone-800">About the department</h2>
            <p class="text-sm leading-relaxed text-stone-500">
                {{ $doctor->doctorProfile->department->description ?? 'No description is available for this department.' }}
            </p>
            <a href="{{ route('departments.show', $doctor->doctorProfile->department) }}"
               class="mt-4 inline-flex text-sm font-medium text-[#3f7f72] hover:underline">
                View the department
            </a>
        </div>
    </div>
</section>
@endif

@endsection
