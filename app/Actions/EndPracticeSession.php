<?php

namespace App\Actions;

use App\Models\PracticeSession;

class EndPracticeSession
{
    /** Closing an already closed session is a no-op, not an error. */
    public function handle(PracticeSession $session): PracticeSession
    {
        if ($session->isOpen()) {
            $session->update(['ended_at' => now()]);
        }

        return $session;
    }
}
