<x-tracker.layout>
    <div x-data="trackerApp({
            initialUser: {{ $user ? json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'is_patient' => $user->isPatient()]) : 'null' }},
            initialJourney: {{ $activeJourney ? json_encode($activeJourney) : 'null' }},
            initialSchedule: {{ $activeSchedule ? json_encode($activeSchedule) : 'null' }},
            initialDoses: {{ json_encode($todayDoses) }},
            initialStats: {
                totalDue: {{ $allDosesCount }},
                taken: {{ $takenDosesCount }},
                missed: {{ $missedDosesCount }},
                points: {{ $totalPoints }},
                adherenceRate: {{ $activeJourney ? $activeJourney->adherence_rate : 100 }}
            },
            initialRewards: {{ json_encode($rewards) }},
            initialCalendar: {{ json_encode($monthlyCalendar) }},
            todayDate: '{{ $today }}'
         })" 
         x-cloak
         class="min-h-screen flex flex-col justify-between max-w-lg mx-auto bg-white shadow-2xl relative border-x border-slate-100">

        <!-- Top Header Navigation -->
        <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-100 px-5 py-3.5 flex items-center justify-between safe-top">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-teal-700 to-teal-500 flex items-center justify-center text-white shadow-md shadow-teal-700/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v12m-6-6h12" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-base font-bold font-display tracking-tight text-slate-900 leading-none">Medixa Tracker</h1>
                    <span class="text-[11px] font-medium text-teal-600 flex items-center gap-1 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-pulse"></span>
                        Recovery Garden
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-1.5">
                <!-- PWA Install Button (Shown if installable) -->
                <button x-show="canInstall" 
                        @click="installPwa()" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-teal-50 text-teal-700 text-xs font-bold border border-teal-200/80 hover:bg-teal-100 transition shadow-sm animate-pulse-gentle">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Install
                </button>

                <!-- iOS Safari Install Guidance Trigger -->
                <button x-show="isIos && !isStandalone" 
                        @click="showIosModal = true" 
                        class="p-2 rounded-xl text-slate-400 hover:text-teal-600 hover:bg-slate-50 transition" 
                        title="Add to Home Screen instructions">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                </button>

                <!-- Notifications Settings Trigger -->
                <button @click="showNotificationModal = true" class="p-2 rounded-xl text-slate-400 hover:text-teal-600 hover:bg-slate-50 transition" title="Notification Settings">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </button>

                <!-- Profile Drawer Trigger -->
                <button @click="showProfileDrawer = true" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-50 transition" title="Profile & Safety">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </button>
            </div>
        </header>

        <!-- Main Scrollable Content Body -->
        <main class="flex-1 overflow-y-auto px-5 py-5 space-y-6">

            <!-- 1. GUEST / NOT SIGNED IN VIEW -->
            <template x-if="!user || !user.is_patient">
                <div class="py-10 text-center space-y-6">
                    <div class="w-20 h-20 mx-auto rounded-3xl bg-teal-50 border border-teal-100 flex items-center justify-center text-teal-600 shadow-md">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold font-display text-slate-900">Patient Sign In Required</h2>
                        <p class="text-sm text-slate-600 mt-2 max-w-xs mx-auto leading-relaxed">
                            Sign in with your Medixa patient credentials to load your prescribed medications and Recovery Garden.
                        </p>
                    </div>
                    <div class="pt-4 flex flex-col gap-3">
                        <a href="{{ route('login') }}" class="w-full py-3.5 px-4 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-semibold text-sm shadow-lg shadow-teal-600/25 transition">
                            Sign In to Medixa
                        </a>
                        <a href="{{ route('home') }}" class="w-full py-3 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs transition">
                            &larr; Back to Hospital Portal
                        </a>
                    </div>
                </div>
            </template>

            <!-- 2. NO ACTIVE JOURNEY / ONBOARDING VIEW -->
            <template x-if="user && user.is_patient && !journey">
                <div class="py-8 text-center space-y-5">
                    <div class="w-20 h-20 mx-auto rounded-3xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-md">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold font-display text-slate-900">No Active Prescription</h2>
                        <p class="text-sm text-slate-600 mt-2 max-w-sm mx-auto leading-relaxed">
                            Your Medicine Tracker will automatically activate when your attending doctor issues a prescription during your OPD visit.
                        </p>
                    </div>
                    <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-2xl text-left text-xs text-slate-600 space-y-2">
                        <span class="font-bold text-slate-800 uppercase tracking-wider text-[10px]">What is the Recovery Garden?</span>
                        <p>A personalized adherence journey scaled to your doctor's exact prescription duration. As you record your daily prescribed doses, your plant sprouts, flourishes, and reaches full bloom.</p>
                    </div>
                    <a href="{{ route('patient.dashboard') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-teal-600 text-white font-semibold text-sm shadow-md hover:bg-teal-700 transition">
                        View Patient Dashboard
                    </a>
                </div>
            </template>

            <!-- 3. ACTIVE RECOVERY JOURNEY CONTENT -->
            <template x-if="user && user.is_patient && journey">
                <div class="space-y-6">

                    <!-- Consecutive Missed Alert / Safety Banner -->
                    <template x-if="journey.consecutive_missed_days >= 2">
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3 shadow-sm">
                            <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div class="text-xs text-amber-900 leading-relaxed">
                                <strong class="block text-sm font-bold text-amber-950 mb-0.5">Clinical Safety Guidance</strong>
                                You have recorded missed medication for 2 consecutive days. <strong>Never take a double dose.</strong> Please consult your attending doctor or pharmacist.
                            </div>
                        </div>
                    </template>

                    <!-- TAB 1: DASHBOARD VIEW -->
                    <div x-show="currentTab === 'today'" class="space-y-6">

                        <!-- Greeting & Journey Status Card -->
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold text-teal-600 uppercase tracking-wider">OPD Recovery Plan</span>
                                <h2 class="text-xl font-bold font-display text-slate-900 mt-0.5" x-text="`Hello, ${user.name}`"></h2>
                            </div>
                            <div class="px-3.5 py-1.5 rounded-full bg-teal-50 border border-teal-200 text-teal-800 text-xs font-bold tracking-wide">
                                <span x-text="`Day ${journey.current_day} of ${journey.total_days || 7}`"></span>
                            </div>
                        </div>

                        <!-- 7-DAY RECOVERY GARDEN VISUALIZER (MDP-INSPIRED PLANT) -->
                        <div class="bg-gradient-to-b from-teal-50/60 to-white rounded-3xl p-6 border border-teal-100 shadow-sm relative overflow-hidden text-center">
                            
                            <!-- State Badge -->
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full text-white"
                                      :class="{
                                          'bg-amber-600': journey.current_state === 'W_WILTED',
                                          'bg-teal-600': journey.current_state === 'S3_BLOOM',
                                          'bg-emerald-600': journey.current_state === 'S2_GROWING',
                                          'bg-teal-500': journey.current_state === 'S1_SPROUT',
                                          'bg-slate-600': journey.current_state === 'S0_SEED'
                                      }"
                                      x-text="getGardenStateLabel(journey.current_state)">
                                </span>
                                
                                <span class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                                    <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <span x-text="`${stats.points} pts`"></span>
                                </span>
                            </div>

                            <!-- Animated Plant Illustration (SVG based on state) -->
                            <div class="h-44 flex items-center justify-center my-1">
                                <!-- S0: SEED UNDERGROUND -->
                                <template x-if="journey.current_state === 'S0_SEED'">
                                    <svg class="w-36 h-36 animate-pulse-gentle" viewBox="0 0 120 120" fill="none">
                                        <!-- Rich Soil mound -->
                                        <ellipse cx="60" cy="85" rx="45" ry="14" fill="#d97706" fill-opacity="0.2"/>
                                        <ellipse cx="60" cy="84" rx="40" ry="10" fill="#78350f" fill-opacity="0.3"/>
                                        <!-- Dormant Golden Seed -->
                                        <ellipse cx="60" cy="80" rx="9" ry="6" fill="#b45309" stroke="#78350f" stroke-width="2"/>
                                        <!-- Droplet of healing water -->
                                        <path d="M60 40 C60 40 52 54 52 60 C52 64.4 55.6 68 60 68 C64.4 68 68 64.4 68 60 C68 54 60 40 60 40 Z" fill="#38bdf8" fill-opacity="0.8"/>
                                    </svg>
                                </template>

                                <!-- S1: SPROUT (Days 2-4) -->
                                <template x-if="journey.current_state === 'S1_SPROUT'">
                                    <svg class="w-36 h-36" viewBox="0 0 120 120" fill="none">
                                        <!-- Soil -->
                                        <ellipse cx="60" cy="90" rx="45" ry="12" fill="#78350f" fill-opacity="0.25"/>
                                        <!-- Stem -->
                                        <path d="M60 90 Q58 65 60 52" stroke="#16a34a" stroke-width="4" stroke-linecap="round"/>
                                        <!-- Left Sprout Leaf -->
                                        <path d="M60 62 Q45 52 46 44 Q56 46 60 58" fill="#4ade80" stroke="#16a34a" stroke-width="1.5"/>
                                        <!-- Right Sprout Leaf -->
                                        <path d="M60 56 Q75 48 74 40 Q64 42 60 52" fill="#22c55e" stroke="#15803d" stroke-width="1.5"/>
                                    </svg>
                                </template>

                                <!-- S2: GROWING PLANT (Days 5-6) -->
                                <template x-if="journey.current_state === 'S2_GROWING'">
                                    <svg class="w-36 h-36" viewBox="0 0 120 120" fill="none">
                                        <ellipse cx="60" cy="95" rx="45" ry="12" fill="#78350f" fill-opacity="0.25"/>
                                        <!-- Strong stem -->
                                        <path d="M60 95 Q62 60 60 38" stroke="#15803d" stroke-width="5" stroke-linecap="round"/>
                                        <!-- Tier 1 Foliage -->
                                        <path d="M60 75 Q38 68 38 56 Q52 58 60 70" fill="#22c55e" stroke="#15803d" stroke-width="2"/>
                                        <path d="M60 70 Q82 64 82 52 Q68 54 60 66" fill="#16a34a" stroke="#15803d" stroke-width="2"/>
                                        <!-- Tier 2 Upper Foliage -->
                                        <path d="M60 52 Q44 42 46 32 Q56 36 60 48" fill="#4ade80" stroke="#16a34a" stroke-width="2"/>
                                        <path d="M60 48 Q76 38 74 28 Q64 32 60 44" fill="#22c55e" stroke="#15803d" stroke-width="2"/>
                                        <!-- Bud on top -->
                                        <circle cx="60" cy="34" r="5" fill="#f43f5e"/>
                                    </svg>
                                </template>

                                <!-- S3: FULL BLOOM FLOWER (Day 7 Milestone!) -->
                                <template x-if="journey.current_state === 'S3_BLOOM'">
                                    <svg class="w-36 h-36" viewBox="0 0 120 120" fill="none">
                                        <ellipse cx="60" cy="98" rx="46" ry="12" fill="#78350f" fill-opacity="0.25"/>
                                        <path d="M60 98 Q59 65 60 44" stroke="#15803d" stroke-width="5" stroke-linecap="round"/>
                                        <!-- Leaves -->
                                        <path d="M60 78 Q36 72 38 60 Q52 64 60 74" fill="#22c55e" stroke="#15803d" stroke-width="2"/>
                                        <path d="M60 74 Q84 68 82 56 Q68 60 60 70" fill="#16a34a" stroke="#15803d" stroke-width="2"/>
                                        <!-- Flower Petals in Bloom -->
                                        <circle cx="60" cy="32" r="8" fill="#ec4899"/>
                                        <circle cx="48" cy="40" r="8" fill="#f43f5e"/>
                                        <circle cx="72" cy="40" r="8" fill="#f43f5e"/>
                                        <circle cx="52" cy="52" r="8" fill="#fb7185"/>
                                        <circle cx="68" cy="52" r="8" fill="#fb7185"/>
                                        <!-- Golden Flower Center -->
                                        <circle cx="60" cy="44" r="9" fill="#facc15" stroke="#eab308" stroke-width="2"/>
                                    </svg>
                                </template>

                                <!-- W: WILTED / PAUSED WARNING STATE -->
                                <template x-if="journey.current_state === 'W_WILTED'">
                                    <svg class="w-36 h-36" viewBox="0 0 120 120" fill="none">
                                        <ellipse cx="60" cy="92" rx="45" ry="12" fill="#78350f" fill-opacity="0.25"/>
                                        <!-- Drooping curved stem -->
                                        <path d="M60 92 Q68 70 52 50" stroke="#a16207" stroke-width="4" stroke-linecap="round"/>
                                        <!-- Drooping wilted yellow/brown leaves -->
                                        <path d="M56 68 Q38 72 40 82 Q50 78 54 70" fill="#ca8a04" stroke="#854d0e" stroke-width="1.5"/>
                                        <path d="M52 52 Q32 50 30 62 Q44 60 50 54" fill="#a16207" stroke="#713f12" stroke-width="1.5"/>
                                    </svg>
                                </template>
                            </div>

                            <p class="text-xs text-slate-600 font-medium" x-text="getGardenMotivationText(journey.current_state)"></p>

                            <!-- Adherence Progress Bar -->
                            <div class="mt-4 pt-4 border-t border-teal-100/80">
                                <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
                                    <span class="text-slate-600">Overall Adherence</span>
                                    <span class="text-teal-700" x-text="`${journey.adherence_rate}%`"></span>
                                </div>
                                <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-teal-500 to-emerald-500 rounded-full transition-all duration-500" 
                                         :style="`width: ${journey.adherence_rate}%`"></div>
                                </div>
                            </div>
                        </div>

                        <!-- TODAY'S MEDICATIONS LIST -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-bold text-slate-900 font-display">Today's Prescribed Doses</h3>
                                <span class="text-xs text-slate-400 font-medium" x-text="`${doses.filter(d => d.status === 'taken').length}/${doses.length} completed`"></span>
                            </div>

                            <template x-if="doses.length === 0">
                                <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                                    <p class="text-xs text-slate-500">No remaining doses scheduled for today.</p>
                                </div>
                            </template>

                            <template x-for="dose in doses" :key="dose.id">
                                <div class="bg-white rounded-2xl p-4 border transition-all duration-200 shadow-sm"
                                     :class="{
                                         'border-emerald-200 bg-emerald-50/30': dose.status === 'taken',
                                         'border-amber-200 bg-amber-50/30': dose.status === 'missed' || dose.status === 'skipped',
                                         'border-slate-200 hover:border-teal-300': dose.status === 'pending'
                                     }">
                                    
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-start gap-3">
                                            <!-- Slot Icon Indicator -->
                                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                                 :class="{
                                                     'bg-emerald-100 text-emerald-700': dose.status === 'taken',
                                                     'bg-amber-100 text-amber-700': dose.status === 'missed' || dose.status === 'skipped',
                                                     'bg-teal-50 text-teal-600': dose.status === 'pending'
                                                 }">
                                                <template x-if="dose.status === 'taken'">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                </template>
                                                <template x-if="dose.status !== 'taken'">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                                </template>
                                            </div>

                                            <div>
                                                <h4 class="text-sm font-bold text-slate-900" x-text="dose.medicine_name"></h4>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span class="text-xs font-semibold text-teal-700" x-text="dose.dosage"></span>
                                                    <span class="text-xs text-slate-300">&bull;</span>
                                                    <span class="text-xs text-slate-500 capitalize" x-text="`${dose.slot} (${formatTime(dose.scheduled_time)})`"></span>
                                                </div>
                                                <template x-if="dose.instructions">
                                                    <p class="text-[11px] text-slate-500 mt-1 italic" x-text="dose.instructions"></p>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Status Badge -->
                                        <div>
                                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                                                  :class="{
                                                      'bg-emerald-100 text-emerald-800': dose.status === 'taken',
                                                      'bg-amber-100 text-amber-800': dose.status === 'missed',
                                                      'bg-slate-100 text-slate-700': dose.status === 'skipped',
                                                      'bg-teal-50 text-teal-700': dose.status === 'pending'
                                                  }"
                                                  x-text="dose.status">
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                                        <template x-if="dose.status === 'pending'">
                                            <div class="flex items-center gap-2 w-full">
                                                <button @click="confirmDose(dose)" 
                                                        class="flex-1 py-2 px-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-sm shadow-teal-600/20 flex items-center justify-center gap-1.5 transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    Take Medicine
                                                </button>
                                                <button @click="openSkipModal(dose)" 
                                                        class="py-2 px-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition" 
                                                        title="Report missed or skipped">
                                                    Skip
                                                </button>
                                            </div>
                                        </template>

                                        <template x-if="dose.status === 'taken'">
                                            <span class="text-[11px] text-emerald-700 font-medium flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                Dose confirmed
                                            </span>
                                        </template>

                                        <template x-if="dose.status === 'missed' || dose.status === 'skipped'">
                                            <span class="text-[11px] text-amber-700 font-medium capitalize" x-text="`Reported ${dose.status}`"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- TAB 2: GARDEN & REWARDS VIEW -->
                    <div x-show="currentTab === 'garden'" class="space-y-6">
                        <div class="text-center">
                            <h3 class="text-lg font-bold font-display text-slate-900">Your Recovery Milestones</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Every verified dose nourishes your recovery.</p>
                        </div>

                        <!-- 4 Dynamic Recovery Milestones Visual Sequence -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-4 rounded-2xl border text-center transition"
                                 :class="journey.current_day >= 1 ? 'bg-teal-50/50 border-teal-200' : 'bg-slate-50 border-slate-100 opacity-60'">
                                <div class="text-2xl mb-1">🌱</div>
                                <h4 class="text-xs font-bold text-slate-900" x-text="getSproutStartDay() > 2 ? `Days 1–${getSproutStartDay() - 1}: Seed` : 'Day 1: Seed'"></h4>
                                <span class="text-[10px] text-slate-500">Journey initiated</span>
                            </div>

                            <div class="p-4 rounded-2xl border text-center transition"
                                 :class="journey.current_day >= getSproutStartDay() && journey.current_state !== 'W_WILTED' ? 'bg-teal-50/50 border-teal-200' : 'bg-slate-50 border-slate-100 opacity-60'">
                                <div class="text-2xl mb-1">🌿</div>
                                <h4 class="text-xs font-bold text-slate-900" x-text="`Days ${getSproutStartDay()}–${getGrowingStartDay() - 1}: Sprout`"></h4>
                                <span class="text-[10px] text-slate-500">Early adherence</span>
                            </div>

                            <div class="p-4 rounded-2xl border text-center transition"
                                 :class="journey.current_day >= getGrowingStartDay() && journey.current_state !== 'W_WILTED' ? 'bg-emerald-50/50 border-emerald-200' : 'bg-slate-50 border-slate-100 opacity-60'">
                                <div class="text-2xl mb-1">🪴</div>
                                <h4 class="text-xs font-bold text-slate-900" x-text="`Days ${getGrowingStartDay()}–${(journey.total_days || 7) - 1}: Growing`"></h4>
                                <span class="text-[10px] text-slate-500">Flourishing habit</span>
                            </div>

                            <div class="p-4 rounded-2xl border text-center transition"
                                 :class="journey.current_state === 'S3_BLOOM' ? 'bg-rose-50 border-rose-200' : 'bg-slate-50 border-slate-100 opacity-60'">
                                <div class="text-2xl mb-1">🌸</div>
                                <h4 class="text-xs font-bold text-slate-900" x-text="`Day ${journey.total_days || 7}: Full Bloom`"></h4>
                                <span class="text-[10px] text-slate-500">Recovery complete!</span>
                            </div>
                        </div>

                        <!-- MONTH-WISE SEED & BLOOM ADHERENCE CALENDAR -->
                        <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                        <span>Monthly Seed Tracker</span>
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-teal-50 text-teal-700 border border-teal-200"
                                              x-text="`${journey.total_days || 7} Days Total`"></span>
                                    </h4>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Track your seeds flourishing into bloom across every prescription day.</p>
                                </div>
                            </div>

                            <!-- Month Navigation Tabs (Month 1, Month 2, Month 3 ...) -->
                            <template x-if="calendar.length > 0">
                                <div>
                                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
                                        <template x-for="(m, mIndex) in calendar" :key="m.month_key">
                                            <button type="button"
                                                    @click="selectedMonthIndex = mIndex"
                                                    class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition flex items-center gap-1.5"
                                                    :class="selectedMonthIndex === mIndex 
                                                        ? 'bg-teal-600 text-white shadow-sm shadow-teal-600/20' 
                                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                                                <span x-text="m.month_name"></span>
                                                <span class="text-[10px] opacity-80" x-text="`(${m.days_in_calendar_month}d)`"></span>
                                            </button>
                                        </template>
                                    </div>

                                    <!-- Current Selected Month View -->
                                    <template x-if="calendar[selectedMonthIndex]">
                                        <div class="mt-3 space-y-3">
                                            <div class="flex items-center justify-between px-1 text-xs">
                                                <span class="font-bold text-slate-700" x-text="calendar[selectedMonthIndex].month_name"></span>
                                                <span class="text-[11px] text-slate-500 font-medium" 
                                                      x-text="`Month has ${calendar[selectedMonthIndex].days_in_calendar_month} calendar days &bull; ${calendar[selectedMonthIndex].days.length} prescribed`"></span>
                                            </div>

                                            <!-- Daily Seed Grid -->
                                            <div class="grid grid-cols-5 sm:grid-cols-7 gap-2">
                                                <template x-for="day in calendar[selectedMonthIndex].days" :key="day.date">
                                                    <div class="p-2 rounded-xl border text-center transition flex flex-col items-center justify-between min-h-[70px] relative"
                                                         :class="{
                                                             'bg-teal-50/70 border-teal-300 ring-2 ring-teal-400': day.is_today,
                                                             'bg-emerald-50 border-emerald-200 text-emerald-900': day.status === 'completed' && !day.is_today,
                                                             'bg-amber-50 border-amber-200 text-amber-900': day.status === 'missed',
                                                             'bg-slate-50 border-slate-200/80 text-slate-700': day.status === 'upcoming'
                                                         }">
                                                        
                                                        <!-- Calendar Date & Journey Day -->
                                                        <div class="w-full flex items-center justify-between text-[10px] font-semibold text-slate-500 px-0.5">
                                                            <span x-text="day.weekday"></span>
                                                            <span class="font-bold text-slate-800" x-text="day.calendar_day"></span>
                                                        </div>

                                                        <!-- Dynamic Plant Stage Seed Icon -->
                                                        <div class="my-1">
                                                            <template x-if="day.status === 'missed'">
                                                                <span class="text-base" title="Missed Dose">🍂</span>
                                                            </template>
                                                            <template x-if="day.status !== 'missed' && day.stage === 'bloom'">
                                                                <span class="text-base" title="Full Bloom">🌸</span>
                                                            </template>
                                                            <template x-if="day.status !== 'missed' && day.stage === 'growing'">
                                                                <span class="text-base" title="Growing Leaf">🪴</span>
                                                            </template>
                                                            <template x-if="day.status !== 'missed' && day.stage === 'sprout'">
                                                                <span class="text-base" title="Sprout">🌿</span>
                                                            </template>
                                                            <template x-if="day.status !== 'missed' && day.stage === 'seed'">
                                                                <span class="text-base" title="Seed">🌱</span>
                                                            </template>
                                                        </div>

                                                        <!-- Day Label / Status Pill -->
                                                        <div class="text-[9px] font-bold uppercase tracking-tight">
                                                            <template x-if="day.is_today">
                                                                <span class="text-teal-700 font-extrabold">Today</span>
                                                            </template>
                                                            <template x-if="!day.is_today && day.status === 'completed'">
                                                                <span class="text-emerald-700">✓ Taken</span>
                                                            </template>
                                                            <template x-if="!day.is_today && day.status === 'missed'">
                                                                <span class="text-amber-700">Missed</span>
                                                            </template>
                                                            <template x-if="!day.is_today && day.status === 'upcoming'">
                                                                <span class="text-slate-400" x-text="`Day ${day.journey_day}`"></span>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>

                                            <!-- Legend -->
                                            <div class="flex flex-wrap items-center justify-center gap-3 pt-2 text-[10px] text-slate-500 border-t border-slate-100">
                                                <span class="flex items-center gap-1"><span>🌱</span> Seed</span>
                                                <span class="flex items-center gap-1"><span>🌿</span> Sprout</span>
                                                <span class="flex items-center gap-1"><span>🪴</span> Growing</span>
                                                <span class="flex items-center gap-1"><span>🌸</span> Bloom</span>
                                                <span class="flex items-center gap-1 text-emerald-600 font-semibold"><span>✓</span> Taken</span>
                                                <span class="flex items-center gap-1 text-amber-600 font-semibold"><span>🍂</span> Missed</span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Unlocked Badges List -->
                        <div class="space-y-3">
                            <h4 class="text-sm font-bold text-slate-900">Earned Badges & Points</h4>
                            <template x-if="rewards.length === 0">
                                <p class="text-xs text-slate-400">Take your first scheduled dose to unlock your first badge.</p>
                            </template>
                            <div class="space-y-2">
                                <template x-for="reward in rewards" :key="reward.id">
                                    <div class="p-3 bg-white rounded-xl border border-slate-100 flex items-center justify-between shadow-sm">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-base">
                                                ★
                                            </div>
                                            <div>
                                                <h5 class="text-xs font-bold text-slate-900" x-text="reward.badge_name"></h5>
                                                <p class="text-[11px] text-slate-500" x-text="reward.description"></p>
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold text-amber-600" x-text="`+${reward.points} pts`"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: ADHERENCE HISTORY VIEW -->
                    <div x-show="currentTab === 'history'" class="space-y-5">
                        <div class="text-center">
                            <h3 class="text-lg font-bold font-display text-slate-900">Adherence History</h3>
                            <p class="text-xs text-slate-500" x-text="`Consolidated record of your ${journey ? journey.total_days || 7 : 7}-day treatment course.`"></p>
                        </div>

                        <!-- Summary Numbers -->
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
                                <span class="text-2xl font-extrabold text-slate-900" x-text="stats.totalDue"></span>
                                <p class="text-[10px] font-semibold text-slate-400 uppercase mt-0.5">Total Doses</p>
                            </div>
                            <div class="p-3.5 bg-emerald-50 rounded-2xl border border-emerald-100">
                                <span class="text-2xl font-extrabold text-emerald-700" x-text="stats.taken"></span>
                                <p class="text-[10px] font-semibold text-emerald-600 uppercase mt-0.5">Taken</p>
                            </div>
                            <div class="p-3.5 bg-amber-50 rounded-2xl border border-amber-100">
                                <span class="text-2xl font-extrabold text-amber-700" x-text="stats.missed"></span>
                                <p class="text-[10px] font-semibold text-amber-600 uppercase mt-0.5">Missed/Skip</p>
                            </div>
                        </div>

                        <!-- Clinical Advice Reminder -->
                        <div class="p-4 bg-teal-50/70 border border-teal-100 rounded-2xl text-xs text-teal-900 leading-relaxed">
                            <strong>Note on Adherence:</strong> Your records reflect self-reported compliance. If you ever have adverse symptoms or need to adjust your medication, consult your doctor.
                        </div>
                    </div>

                </div>
            </template>
        </main>

        <!-- Bottom Navigation Bar (Persistent Mobile PWA Nav) -->
        <nav class="sticky bottom-0 z-30 bg-white/95 backdrop-blur-md border-t border-slate-100 px-6 py-2.5 flex items-center justify-around safe-bottom">
            <button @click="currentTab = 'today'" 
                    class="flex flex-col items-center gap-1 transition"
                    :class="currentTab === 'today' ? 'text-teal-600 font-bold' : 'text-slate-400 hover:text-slate-600'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span class="text-[10px]">Today</span>
            </button>

            <button @click="currentTab = 'garden'" 
                    class="flex flex-col items-center gap-1 transition"
                    :class="currentTab === 'garden' ? 'text-teal-600 font-bold' : 'text-slate-400 hover:text-slate-600'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span class="text-[10px]">Garden</span>
            </button>

            <button @click="currentTab = 'history'" 
                    class="flex flex-col items-center gap-1 transition"
                    :class="currentTab === 'history' ? 'text-teal-600 font-bold' : 'text-slate-400 hover:text-slate-600'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-[10px]">History</span>
            </button>
        </nav>

        <!-- MODAL: SKIP / MISSED DOSE REASON -->
        <div x-show="showSkipModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-4">
            <div @click.away="showSkipModal = false" class="bg-white rounded-3xl p-6 max-w-sm w-full space-y-4 shadow-2xl">
                <h4 class="text-base font-bold text-slate-900">Report Missed or Skipped Dose</h4>
                <p class="text-xs text-slate-500">Provide an optional reason so your clinical record remains accurate.</p>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason</label>
                    <select x-model="skipReason" class="w-full text-xs rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-200">
                        <option value="Forgot to take on time">Forgot to take on time</option>
                        <option value="Felt better / symptoms subsided">Felt better / symptoms subsided</option>
                        <option value="Experienced mild nausea or upset stomach">Experienced mild nausea or upset stomach</option>
                        <option value="Medication temporarily unavailable">Medication temporarily unavailable</option>
                        <option value="Doctor or clinician advised to pause">Doctor or clinician advised to pause</option>
                    </select>
                </div>
                <div class="flex gap-2 pt-2">
                    <button @click="submitSkipDose('skipped')" class="flex-1 py-2.5 rounded-xl bg-amber-600 text-white font-semibold text-xs shadow-sm hover:bg-amber-700 transition">
                        Confirm Skip
                    </button>
                    <button @click="showSkipModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-medium hover:bg-slate-200 transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: NOTIFICATIONS SETTINGS -->
        <div x-show="showNotificationModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="showNotificationModal = false" class="bg-white rounded-3xl p-6 max-w-sm w-full space-y-4 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h4 class="text-base font-bold text-slate-900">Medication Reminders</h4>
                    <button @click="showNotificationModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Enable browser notifications to receive punctual alerts when morning, afternoon, or night doses are scheduled.
                </p>
                <button @click="requestNotificationPermission()" class="w-full py-3 rounded-xl bg-teal-600 text-white font-semibold text-xs shadow-md shadow-teal-600/20 hover:bg-teal-700 transition">
                    Enable Web Push Reminders
                </button>
            </div>
        </div>

        <!-- MODAL: iOS / Safari ADD TO HOME SCREEN GUIDANCE -->
        <div x-show="showIosModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-end justify-center p-4">
            <div @click.away="showIosModal = false" class="bg-white rounded-3xl p-6 max-w-sm w-full space-y-4 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h4 class="text-base font-bold text-slate-900">Install on iPhone / iPad</h4>
                    <button @click="showIosModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <div class="space-y-3 text-xs text-slate-600">
                    <div class="flex items-start gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-teal-100 text-teal-800 font-bold flex items-center justify-center shrink-0">1</span>
                        <span>Tap the <strong>Share</strong> button at the bottom of Safari (<svg class="w-3.5 h-3.5 inline text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>).</span>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-teal-100 text-teal-800 font-bold flex items-center justify-center shrink-0">2</span>
                        <span>Scroll down and tap <strong>Add to Home Screen</strong>.</span>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-teal-100 text-teal-800 font-bold flex items-center justify-center shrink-0">3</span>
                        <span>Tap <strong>Add</strong> in the top right corner.</span>
                    </div>
                </div>
                <button @click="showIosModal = false" class="w-full py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                    Got it
                </button>
            </div>
        </div>

        <!-- DRAWER: PROFILE & SAFETY -->
        <div x-show="showProfileDrawer" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex justify-end">
            <div @click.away="showProfileDrawer = false" class="bg-white w-80 h-full p-6 flex flex-col justify-between shadow-2xl">
                <div class="space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <h4 class="text-base font-bold text-slate-900">Patient Account</h4>
                        <button @click="showProfileDrawer = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                    </div>

                    <div class="space-y-1">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Signed in as</span>
                        <h5 class="text-sm font-bold text-slate-900" x-text="user ? user.name : 'Guest'"></h5>
                        <p class="text-xs text-slate-500" x-text="user ? user.email : ''"></p>
                    </div>

                    <!-- Medical Safety Box -->
                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-[11px] text-slate-600 space-y-1.5 leading-relaxed">
                        <span class="font-bold text-slate-800 uppercase tracking-wider block text-[10px]">Medical Safety Notice</span>
                        <p>Medixa Medicine Tracker is a personal adherence logging tool. It does not replace medical advice. Never change your prescribed regimen without clinician approval.</p>
                    </div>

                    <div class="pt-2 space-y-2">
                        <a href="{{ route('patient.dashboard') }}" class="block py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold text-center transition">
                            Back to Medixa Portal
                        </a>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-semibold transition">
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- Alpine.js Application Logic -->
    <script>
        function trackerApp(config) {
            return {
                user: config.initialUser,
                journey: config.initialJourney,
                schedule: config.initialSchedule,
                doses: config.initialDoses || [],
                stats: config.initialStats || { totalDue: 0, taken: 0, missed: 0, points: 0, adherenceRate: 100 },
                rewards: config.initialRewards || [],
                calendar: config.initialCalendar || [],
                selectedMonthIndex: 0,
                currentTab: 'today',
                canInstall: false,
                isIos: /iphone|ipad|ipod/i.test(navigator.userAgent),
                isStandalone: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
                showIosModal: false,
                showNotificationModal: false,
                showProfileDrawer: false,
                showSkipModal: false,
                selectedDose: null,
                skipReason: 'Forgot to take on time',

                init() {
                    window.addEventListener('pwa-installable', () => {
                        this.canInstall = true;
                    });
                    if (window.deferredPwaPrompt) {
                        this.canInstall = true;
                    }
                },

                installPwa() {
                    if (window.deferredPwaPrompt) {
                        window.deferredPwaPrompt.prompt();
                        window.deferredPwaPrompt.userChoice.then((choiceResult) => {
                            if (choiceResult.outcome === 'accepted') {
                                this.canInstall = false;
                            }
                            window.deferredPwaPrompt = null;
                        });
                    }
                },

                requestNotificationPermission() {
                    if ('Notification' in window) {
                        Notification.requestPermission().then(permission => {
                            if (permission === 'granted') {
                                alert('Reminders enabled! You will receive dose alerts.');
                                this.showNotificationModal = false;
                            } else {
                                alert('Notification permission was declined.');
                            }
                        });
                    } else {
                        alert('Your browser does not support Web Push notifications.');
                    }
                },

                getSproutStartDay() {
                    const total = this.journey?.total_days || 7;
                    return Math.max(2, Math.round(total * 0.25));
                },

                getGrowingStartDay() {
                    const total = this.journey?.total_days || 7;
                    const sprout = this.getSproutStartDay();
                    return Math.max(sprout + 1, Math.round(total * 0.65));
                },

                formatTime(timeStr) {
                    if (!timeStr) return '';
                    const parts = timeStr.split(':');
                    let hours = parseInt(parts[0], 10);
                    const minutes = parts[1];
                    const ampm = hours >= 12 ? 'PM' : 'AM';
                    hours = hours % 12;
                    hours = hours ? hours : 12;
                    return `${hours}:${minutes} ${ampm}`;
                },

                getGardenStateLabel(state) {
                    const labels = {
                        'S0_SEED': 'Day 1: Seed in Soil',
                        'S1_SPROUT': 'Sprouting Healthy',
                        'S2_GROWING': 'Flourishing Foliage',
                        'S3_BLOOM': 'Recovery in Full Bloom',
                        'W_WILTED': 'Plant Needs Care'
                    };
                    return labels[state] || 'Active';
                },

                getGardenMotivationText(state) {
                    const total = this.journey?.total_days || 7;
                    const messages = {
                        'S0_SEED': 'Your healing journey begins today. Take scheduled doses to nourish the seed.',
                        'S1_SPROUT': 'A tiny green sprout emerges! Consistency helps it grow strong.',
                        'S2_GROWING': 'Healthy leaves are unfurling as you stay on track with your prescription.',
                        'S3_BLOOM': `Congratulations! You have completed your ${total}-day Recovery Journey in Full Bloom!`,
                        'W_WILTED': 'Growth is paused due to missed doses. Resume your regular schedule to restore your garden.'
                    };
                    return messages[state] || 'Keep up your daily regimen.';
                },

                async confirmDose(dose) {
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const response = await fetch(`/api/tracker/doses/${dose.id}/confirm`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            }
                        });

                        const data = await response.json();
                        if (data.success) {
                            dose.status = 'taken';
                            this.stats.taken++;
                            if (data.data.journey) {
                                this.journey = data.data.journey;
                            }
                            if (data.data.calendar) {
                                this.calendar = data.data.calendar;
                            }
                        } else {
                            alert(data.error || 'Failed to record dose.');
                        }
                    } catch (e) {
                        alert('Network error. Please try again.');
                    }
                },

                openSkipModal(dose) {
                    this.selectedDose = dose;
                    this.showSkipModal = true;
                },

                async submitSkipDose(status) {
                    if (!this.selectedDose) return;

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const response = await fetch(`/api/tracker/doses/${this.selectedDose.id}/skip`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ reason: this.skipReason })
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.selectedDose.status = status;
                            this.stats.missed++;
                            if (data.data.journey) {
                                this.journey = data.data.journey;
                            }
                            if (data.data.calendar) {
                                this.calendar = data.data.calendar;
                            }
                            this.showSkipModal = false;
                        }
                    } catch (e) {
                        alert('Network error. Please try again.');
                    }
                }
            };
        }
    </script>
</x-tracker.layout>

