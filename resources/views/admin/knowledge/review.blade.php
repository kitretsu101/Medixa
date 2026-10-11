<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-slate-900 leading-tight">{{ $article->title }}</h2>
            <p class="text-sm text-slate-500 mt-1">Record a reviewer's decision. An administrator login does not by itself approve medical content.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <article class="bg-white border border-slate-200 rounded-2xl p-6 space-y-3 text-sm text-slate-700">
                <p><span class="font-semibold text-slate-900">Source:</span> {{ $article->source_name }}</p>
                @if($article->source_url)
                    <p><a href="{{ $article->source_url }}" class="text-teal-700 underline" target="_blank" rel="noopener noreferrer">{{ $article->source_url }}</a></p>
                @endif
                <p class="whitespace-pre-line leading-relaxed">{{ $article->content }}</p>
                <p class="text-xs text-slate-400">Version {{ $article->version }}. Topic {{ str_replace('_', ' ', $article->topic) }}. Language {{ strtoupper($article->language) }}.</p>
            </article>

            <form method="POST" action="{{ route('admin.knowledge.update', $article) }}" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="approval_status" class="block text-sm font-medium text-slate-700">Decision</label>
                    <select id="approval_status" name="approval_status" class="mt-1 block w-full rounded-lg border-slate-300">
                        @foreach(['pending' => 'Pending review', 'approved' => 'Approved by the named reviewer', 'rejected' => 'Rejected'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('approval_status', $article->approval_status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('approval_status')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reviewer_name" class="block text-sm font-medium text-slate-700">Qualified reviewer name</label>
                    <input id="reviewer_name" name="reviewer_name" type="text" value="{{ old('reviewer_name', $article->reviewer_name) }}" class="mt-1 block w-full rounded-lg border-slate-300">
                    <p class="mt-1 text-xs text-slate-500">Required to approve or reject. Enter the person who reviewed the source, not a placeholder.</p>
                    @error('reviewer_name')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="is_active" class="block text-sm font-medium text-slate-700">Active</label>
                    <select id="is_active" name="is_active" class="mt-1 block w-full rounded-lg border-slate-300">
                        <option value="1" @selected(old('is_active', $article->is_active ? '1' : '0') == '1')>Active</option>
                        <option value="0" @selected(old('is_active', $article->is_active ? '1' : '0') == '0')>Inactive</option>
                    </select>
                </div>

                <button type="submit" class="inline-flex px-4 py-2 bg-teal-700 text-white text-sm font-semibold rounded-lg">Save review decision</button>
            </form>
        </div>
    </div>
</x-app-layout>
