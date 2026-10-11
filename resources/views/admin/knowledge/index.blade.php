<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-slate-900 leading-tight">Knowledge candidates</h2>
            <p class="text-sm text-slate-500 mt-1">Pending articles are not clinically approved. Retrieval uses an article only after a named reviewer approves it, and for {{ $reviewDays }} days after that review date.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm">{{ session('success') }}</div>
            @endif

            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Title</th>
                            <th class="px-4 py-3 font-medium">Topic</th>
                            <th class="px-4 py-3 font-medium">Language</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($articles as $article)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3 text-slate-900">{{ $article->title }}</td>
                                <td class="px-4 py-3">{{ str_replace('_', ' ', $article->topic) }}</td>
                                <td class="px-4 py-3 uppercase">{{ $article->language }}</td>
                                <td class="px-4 py-3">
                                    @if($article->approval_status === 'approved')
                                        Approved by {{ $article->reviewer_name }} on {{ $article->review_date?->format('M d, Y') }}
                                    @elseif($article->approval_status === 'rejected')
                                        Rejected
                                    @else
                                        Pending review. Not clinically approved.
                                    @endif
                                    @unless($article->is_active)
                                        <span class="text-slate-400">Inactive.</span>
                                    @endunless
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.knowledge.edit', $article) }}" class="font-semibold text-teal-700">Review</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-slate-500">No knowledge articles have been seeded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
