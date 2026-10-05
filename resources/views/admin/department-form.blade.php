<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-slate-900 leading-tight">
                    {{ $department ? __('Edit Department') : __('Add New Department') }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">Configure department details and clinical description</p>
            </div>
            <a href="{{ route('admin.departments.index') }}" class="px-4 py-2 border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-xl transition">
                &larr; Back to Departments
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">
                <form method="POST" action="{{ $department ? route('admin.departments.update', $department) : route('admin.departments.store') }}" class="space-y-6">
                    @csrf
                    @if($department)
                        @method('PUT')
                    @endif

                    <!-- Department Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">
                            Department Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $department?->name) }}"
                            placeholder="e.g. Cardiology, Neurology, Pediatrics"
                            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            required
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-semibold text-slate-700 mb-1">
                            Clinical Overview & Description
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                            placeholder="Describe department services, specializations, and patient care capabilities..."
                            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        >{{ old('description', $department?->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <a href="{{ route('admin.departments.index') }}" class="px-4 py-2.5 border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-xl transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                            {{ $department ? __('Update Department') : __('Create Department') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
