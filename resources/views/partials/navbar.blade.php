<nav class="sticky top-0 z-50 border-b border-[#21262d]/80 bg-[#0d1117]/90 backdrop-blur-md">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-[#22a08c] to-[#188071] flex items-center justify-center shadow-lg shadow-[#22a08c]/25 group-hover:shadow-[#22a08c]/50 transition-all duration-300">
                    <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </div>
                <span class="font-display text-xl font-bold text-white tracking-tight">Medixa</span>
            </a>

            {{-- Desktop nav links --}}
            <div class="hidden md:flex items-center gap-1">
                <a href="{{ route('home') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium text-[#9198a1] hover:text-white hover:bg-[#21262d] transition-all duration-200 {{ request()->routeIs('home') ? 'text-white bg-[#21262d]' : '' }}">
                    Home
                </a>
                <a href="{{ route('doctors.index') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium text-[#9198a1] hover:text-white hover:bg-[#21262d] transition-all duration-200 {{ request()->routeIs('doctors.*') ? 'text-white bg-[#21262d]' : '' }}">
                    Doctors
                </a>
            </div>

            {{-- Auth CTA --}}
            <div class="flex items-center gap-3">
                <a href="#" class="hidden sm:inline-flex text-sm font-medium text-[#9198a1] hover:text-white transition-colors duration-200">
                    Sign In
                </a>
                <a href="#" class="inline-flex items-center gap-1.5 rounded-lg bg-[#22a08c] px-4 py-2 text-sm font-semibold text-white shadow-md shadow-[#22a08c]/20 hover:bg-[#188071] hover:shadow-[#22a08c]/40 transition-all duration-200">
                    Book Appointment
                </a>
            </div>
        </div>
    </div>
</nav>
