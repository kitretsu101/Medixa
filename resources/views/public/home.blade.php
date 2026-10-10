@extends('layouts.public')

@section('title', 'Medixa — Smart Hospital OPD')
@section('meta_description', 'Welcome to Medixa. Find specialist doctors, browse departments, and book OPD appointments online.')

@section('content')

<section class="relative overflow-hidden pt-16 pb-20 sm:pt-24 sm:pb-28">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -top-24 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-[#d7efe8] blur-3xl"></div>
        <div class="absolute right-0 top-24 h-56 w-56 rounded-full bg-[#f3e6d4] blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <div class="inline-flex items-center gap-2 rounded-full border border-[#d5ebe4] bg-white px-4 py-1.5 shadow-sm">
                <span class="h-2 w-2 rounded-full bg-[#7dbeaf]"></span>
                <span class="text-xs font-semibold uppercase tracking-wider text-[#3f7f72]">Smart OPD · Now open</span>
            </div>

            <h1 class="mt-8 font-display text-4xl font-semibold tracking-tight text-stone-800 sm:text-6xl">
                Care that feels calm,<br>
                <span class="text-[#3f7f72]">from the waiting room on.</span>
            </h1>

            <p class="mx-auto mt-5 max-w-2xl text-base leading-relaxed text-stone-500 sm:text-lg">
                Find a specialist, follow the live chamber queue, and keep prescriptions and lab reports in one quiet place.
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('doctors.index') }}"
                   class="inline-flex items-center gap-2 rounded-full bg-[#5eaea0] px-7 py-3 text-sm font-semibold text-white shadow-sm hover:bg-[#4c9d90] transition">
                    Find a doctor
                </a>
                <a href="#departments"
                   class="inline-flex items-center gap-2 rounded-full border border-stone-200 bg-white px-7 py-3 text-sm font-semibold text-stone-700 hover:border-[#b7ddd4] hover:text-[#3f7f72] transition">
                    Browse departments
                </a>
            </div>
        </div>

        <div class="mx-auto mt-14 grid max-w-3xl grid-cols-1 gap-3 sm:grid-cols-3">
            @foreach([['24/7', 'Emergency line'], ['Live', 'Chamber queue'], ['Private', 'Consent-scoped records']] as [$num, $label])
                <div class="rounded-2xl border border-stone-200/80 bg-white px-5 py-4 text-center shadow-sm">
                    <div class="font-display text-2xl font-semibold text-stone-800">{{ $num }}</div>
                    <div class="mt-1 text-xs text-stone-500">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="departments" class="pb-20">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-[#3f7f72]">Departments</p>
                <h2 class="mt-1 font-display text-3xl font-semibold text-stone-800">Where to start</h2>
            </div>
            <p class="max-w-md text-sm leading-relaxed text-stone-500">Choose a speciality, then open a doctor who is on the schedule today.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($departments as $department)
                <a href="{{ route('departments.show', $department) }}"
                   class="group rounded-3xl border border-stone-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-[#cfe6df] hover:shadow-md">
                    <div class="mb-5 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[#eef7f4] text-[#3f7f72]">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <h3 class="font-display text-lg font-semibold text-stone-800 group-hover:text-[#3f7f72]">{{ $department->name }}</h3>
                    @if($department->description)
                        <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-stone-500">{{ $department->description }}</p>
                    @endif
                    <div class="mt-5 flex items-center justify-between text-xs text-stone-500">
                        <span>{{ $department->doctor_profiles_count }} {{ Str::plural('specialist', $department->doctor_profiles_count) }}</span>
                        <span class="font-semibold text-[#3f7f72]">Open</span>
                    </div>
                </a>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed border-stone-300 bg-white py-12 text-center text-stone-500">
                    No departments are listed yet.
                </div>
            @endforelse
        </div>
    </div>
</section>

<section class="pb-24">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-[2rem] border border-[#e4efe9] bg-[#f3faf7] px-6 py-10 sm:px-10">
            <p class="text-xs font-semibold uppercase tracking-widest text-[#3f7f72]">In the visit</p>
            <h2 class="mt-2 font-display text-3xl font-semibold text-stone-800">A lighter way through OPD</h2>
            <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['01', 'Live queue', 'See the chamber serial and an estimated wait after you check in.'],
                    ['02', 'Your records', 'Prescriptions and reports stay with you until you share them.'],
                    ['03', 'Lab upload', 'The laboratory files a report without opening the rest of the chart.'],
                    ['04', 'Doctor review', 'The clinician decides what the report means. The system does not.'],
                ] as [$step, $title, $desc])
                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <div class="text-xs font-semibold tracking-widest text-[#7dbeaf]">{{ $step }}</div>
                        <h3 class="mt-3 font-display font-semibold text-stone-800">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-stone-500">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

@endsection
