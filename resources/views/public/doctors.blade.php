@extends('layouts.public')

@section('title', 'Find a Doctor — Medixa')
@section('meta_description', 'Browse our directory of specialist doctors. Filter by department to find the right consultant for your needs.')

@section('content')

<section class="border-b border-stone-200 py-12">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-widest text-[#3f7f72] mb-2">Our team</p>
        <h1 class="font-display text-3xl sm:text-4xl font-semibold text-stone-800">Find a doctor</h1>
        <p class="mt-2 text-stone-500">
            {{ $doctors->count() }} specialist{{ $doctors->count() !== 1 ? 's' : '' }} available
            @if($selectedDepartmentId)
                in this department
            @endif
        </p>
    </div>
</section>

<section class="py-12">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mb-10 flex flex-wrap gap-2">
            <a href="{{ route('doctors.index') }}"
               id="filter-all"
               class="rounded-full px-4 py-2 text-sm font-medium transition
                      {{ is_null($selectedDepartmentId) ? 'bg-[#5eaea0] text-white' : 'border border-stone-200 bg-white text-stone-600 hover:border-[#b7ddd4]' }}">
                All departments
            </a>
            @foreach($departments as $dept)
            <a href="{{ route('doctors.index', ['department' => $dept->id]) }}"
               id="filter-dept-{{ $dept->id }}"
               class="rounded-full px-4 py-2 text-sm font-medium transition
                      {{ $selectedDepartmentId === $dept->id ? 'bg-[#5eaea0] text-white' : 'border border-stone-200 bg-white text-stone-600 hover:border-[#b7ddd4]' }}">
                {{ $dept->name }}
            </a>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($doctors as $doctor)
            <a href="{{ route('doctors.show', $doctor) }}"
               id="doctor-card-{{ $doctor->id }}"
               class="group flex flex-col rounded-3xl border border-stone-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-[#cfe6df] hover:shadow-md">
                <div class="mb-4 flex items-start gap-4">
                    <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-[#eef7f4] text-lg font-semibold text-[#3f7f72]">
                        {{ strtoupper(substr($doctor->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate font-display text-base font-semibold text-stone-800 group-hover:text-[#3f7f72]">
                            <x-doctor-name :name="$doctor->name" />
                        </h2>
                        @if($doctor->doctorProfile)
                        <p class="truncate text-sm text-stone-500">{{ $doctor->doctorProfile->designation }}</p>
                        <p class="truncate text-xs font-medium text-[#3f7f72]">
                            {{ $doctor->doctorProfile->department->name ?? '—' }}
                        </p>
                        @endif
                    </div>
                </div>

                @if($doctor->doctorProfile)
                <div class="mt-auto border-t border-stone-100 pt-4">
                    <p class="truncate text-xs text-stone-500">{{ $doctor->doctorProfile->qualification }}</p>
                    <p class="mt-2 text-xs text-stone-400">{{ number_format($doctor->doctorProfile->patients_seen_count) }} patients seen</p>
                </div>
                @endif
            </a>
            @empty
            <div class="col-span-full py-20 text-center text-stone-500">
                No doctors found for this department.
            </div>
            @endforelse
        </div>
    </div>
</section>

@endsection
