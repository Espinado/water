<?php

namespace App\Session;

use Illuminate\Session\SessionManager;

class AppSessionManager extends SessionManager
{
    public function resetDriver(): void
    {
        $driver = $this->getDefaultDriver();

        if ($driver !== null) {
            unset($this->drivers[$driver]);
        }
    }
}
