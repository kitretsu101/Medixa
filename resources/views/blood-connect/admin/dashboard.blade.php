<x-blood-connect.admin-shell>
    <h1 class="text-2xl font-bold">Blood Connect overview</h1>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@foreach($stats as $label => $count)<article class="rounded-2xl border border-stone-200 bg-white p-6"><h2 class="text-sm text-gray-600">{{ $label }}</h2><p class="mt-3 text-3xl font-bold text-red-800">{{ $count }}</p></article>@endforeach</div>
    <p class="text-sm text-gray-600">Requests become public only after approval. Record completed, clinically confirmed donations to track fulfillment.</p>
</x-blood-connect.admin-shell>

