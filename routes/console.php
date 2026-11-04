<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('chat-sessions:prune')->daily()->withoutOverlapping();
Schedule::command('tracker:send-reminders')->everyFifteenMinutes()->withoutOverlapping();
