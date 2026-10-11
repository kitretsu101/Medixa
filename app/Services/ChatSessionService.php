<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Issues and checks chat session identifiers.
 *
 * The row stores no message text. A failed check returns 404 so the response
 * does not confirm that another person's session exists.
 */
class ChatSessionService
{
    public function open(?string $presentedId, ?User $user): ChatSession
    {
        if ($presentedId === null || $presentedId === '') {
            return $this->start($user);
        }

        $session = $this->authorized($presentedId, $user);
        $session->forceFill(['last_used_at' => now()])->save();

        return $session;
    }

    public function delete(string $presentedId, ?User $user): void
    {
        $this->authorized($presentedId, $user)->delete();
    }

    public function start(?User $user): ChatSession
    {
        $days = max(1, (int) config('medixa.chat.retention_days'));

        return ChatSession::query()->create([
            'user_id' => $user?->id,
            'expires_at' => now()->addDays($days),
            'last_used_at' => now(),
        ]);
    }

    private function authorized(string $presentedId, ?User $user): ChatSession
    {
        if (! Str::isUuid($presentedId)) {
            abort(404);
        }

        $session = ChatSession::query()->whereKey($presentedId)->first();

        if ($session === null) {
            abort(404);
        }

        if ($session->expires_at->lte(now())) {
            $session->delete();

            abort(404);
        }

        $owned = $user === null
            ? $session->user_id === null
            : (int) $session->user_id === (int) $user->id;

        if (! $owned) {
            abort(404);
        }

        return $session;
    }
}
