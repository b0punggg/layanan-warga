<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class CatatAutentikasi
{
    public function masuk(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill(['terakhir_login' => now()])->save();

        AuditLogger::catat('masuk', 'pengguna:'.$event->user->id, null, $event->user->id);
    }

    public function keluar(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        AuditLogger::catat('keluar', 'pengguna:'.$event->user->id, null, $event->user->id);
    }
}
