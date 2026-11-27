<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogger
{
    public static function catat(string $aksi, ?string $objek = null, ?array $detail = null, ?int $userId = null): void
    {
        AuditLog::query()->create([
            'user_id' => $userId ?? auth()->id(),
            'aksi' => $aksi,
            'objek' => $objek,
            'detail' => $detail,
            'ip' => request()->ip(),
            'waktu' => now(),
        ]);
    }
}
