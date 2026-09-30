<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
                    {{ __('Unified Medical Timeline') }}
                </h2>
                <p class="text-sm text-slate-500">A consolidated, chronological stream of all your prescriptions and diagnostic lab reports.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('patient.lab-reports.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Upload New Lab Report
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
                <div class="rounded-xl bg-teal-50 border border-teal-200 p-4 flex items-center gap-3 text-teal-800 text-sm shadow-sm">
                    <svg class="w-5 h-5 text-teal-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if($timeline->isEmpty())
                <div class="bg-white rounded-2xl p-12 text-center border border-slate-100 shadow-sm">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">No medical records yet</h3>
                    <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">When your doctors issue prescriptions or you upload diagnostic lab reports, they will appear here chronologically.</p>
                    <div class="mt-6">
                        <a href="{{ route('patient.lab-reports.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700 transition">
                            Upload your first report
                        </a>
                    </div>
                </div>
            @else
                <div class="relative border-l-2 border-slate-200 ml-4 md:ml-6 space-y-8 pb-8">
                    @foreach($timeline as $item)
                        <div class="relative pl-6 md:pl-8 group">
                            <!-- Timeline Node Icon -->
                            @if($item->type === 'prescription')
                                <div class="absolute -left-3.5 top-1 w-7 h-7 rounded-full bg-indigo-600 text-white flex items-center justify-center shadow-md ring-4 ring-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                            @else
                                <div class="absolute -left-3.5 top-1 w-7 h-7 rounded-full bg-teal-600 text-white flex items-center justify-center shadow-md ring-4 ring-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                            @endif

                            <!-- Content Card -->
                            <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider {{ $item->type === 'prescription' ? 'bg-indigo-100 text-indigo-800' : 'bg-teal-100 text-teal-800' }}">
                                            {{ $item->type === 'prescription' ? 'Prescription' : 'Lab Report' }}
                                        </span>
                                        <span class="text-xs text-slate-400">&bull;</span>
                                        <span class="text-xs font-medium text-slate-500">
                                            Dr. {{ $item->doctor_name }}
                                        </span>
                                    </div>
                                    <time class="text-xs font-medium text-slate-400">
                                        {{ $item->date ? $item->date->format('M d, Y &bull; h:i A') : 'N/A' }}
                                    </time>
                                </div>

                                <h3 class="text-lg font-bold text-slate-900 mb-2">{{ $item->title }}</h3>

                                @if($item->type === 'prescription')
                                    @if(!empty($item->detail))
                                        <div class="mb-4 text-sm text-slate-600 bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                                            <strong class="text-slate-800 font-semibold block mb-1">Clinical Instructions:</strong>
                                            {{ $item->detail }}
                                        </div>
                                    @endif

                                    @if(!empty($item->medicines) && is_array($item->medicines))
                                        <div class="mt-3">
                                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Prescribed Medicines</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                @foreach($item->medicines as $med)
                                                    <div class="bg-indigo-50/50 rounded-lg p-2.5 border border-indigo-100/60 text-xs">
                                                        <p class="font-bold text-indigo-950">{{ $med['name'] ?? 'Medicine' }}</p>
                                                        <p class="text-indigo-700 mt-0.5">{{ $med['dosage'] ?? '' }} &bull; {{ $med['frequency'] ?? '' }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-50">
                                        <p class="text-xs font-medium text-slate-600">{{ $item->detail }}</p>
                                        <a href="{{ route('patient.lab-reports.show', $item->id) }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-600 hover:text-teal-800">
                                            <span>View Report File</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
