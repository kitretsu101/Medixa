<?php

namespace App\Services;

use Illuminate\Session\SessionManager;
use Illuminate\Session\Store;

class DonorSessionStore
{
    public Store $session;

    public function __construct(SessionManager $sessions)
    {
        $this->session = clone $sessions->driver('file');
        $this->session->flush();
        $this->session->setName('medixa-donor-auth');
    }
}
