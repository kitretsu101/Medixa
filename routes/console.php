<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('chat-sessions:prune')->daily()->withoutOverlapping();
