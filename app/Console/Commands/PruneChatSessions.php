<?php

namespace App\Console\Commands;

use App\Models\ChatSession;
use Illuminate\Console\Command;

class PruneChatSessions extends Command
{
    protected $signature = 'chat-sessions:prune';

    protected $description = 'Delete expired chat session identifiers';

    public function handle(): int
    {
        $deleted = ChatSession::query()->where('expires_at', '<', now())->delete();

        $this->info('Pruned expired chat sessions: '.$deleted);

        return self::SUCCESS;
    }
}
