<section class="pb-16">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 rounded-3xl border border-red-100 bg-white p-6 shadow-sm sm:p-10">
            <div><p class="text-xs font-semibold uppercase tracking-widest text-red-700">Community blood donation</p>
                <h2 class="mt-2 font-display text-3xl font-semibold text-stone-800"><a href="{{ route('blood-connect.index') }}">Medixa Blood Connect</a></h2>
                <p class="mt-3 text-stone-600">Donate Blood. Save Lives. Be Someone's Hope.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a class="rounded-full bg-red-700 px-6 py-3 text-sm font-semibold text-white hover:bg-red-800" href="{{ route('blood-connect.requests.create') }}">Request Blood</a>
                <a class="rounded-full border border-stone-300 px-6 py-3 text-sm font-semibold text-stone-800" href="{{ route('blood-connect.requests.index') }}">Find Blood Requests</a>
                <a class="rounded-full border border-red-200 px-6 py-3 text-sm font-semibold text-red-800" href="{{ route('blood-connect.donor.register') }}">Become a Donor</a>
            </div>
        </div>
    </div>
</section>

