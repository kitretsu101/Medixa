<nav class="sticky top-0 z-50 border-b border-stone-200/80 bg-[#f7f5f2]/90 backdrop-blur-md">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <x-application-logo class="h-8 w-8" />
                <span class="font-display text-xl font-semibold tracking-tight text-stone-800">Medixa</span>
            </a>

            <div class="hidden md:flex items-center gap-1">
                <a href="{{ route('home') }}"
                   class="rounded-full px-4 py-2 text-sm font-medium transition {{ request()->routeIs('home') ? 'bg-white text-stone-800 shadow-sm' : 'text-stone-500 hover:text-stone-800' }}">
                    Home
                </a>
                <a href="{{ route('doctors.index') }}"
                   class="rounded-full px-4 py-2 text-sm font-medium transition {{ request()->routeIs('doctors.*') ? 'bg-white text-stone-800 shadow-sm' : 'text-stone-500 hover:text-stone-800' }}">
                    Doctors
                </a>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-full bg-[#5eaea0] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4c9d90] transition">
                        {{ Auth::user()->role === 'lab_assistant' ? 'Lab' : ucfirst(Auth::user()->role) }} desk
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-stone-500 hover:text-stone-800">
                            Log out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-stone-600 hover:text-stone-900">
                        Sign in
                    </a>
                    <a href="{{ route('register') }}" class="rounded-full bg-[#5eaea0] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4c9d90] transition">
                        Register
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>
