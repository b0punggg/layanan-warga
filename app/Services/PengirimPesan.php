<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PengirimPesan
{
    public function keAkun(User $user, string $isiTerkirim, ?string $isiTersimpan = null, bool $cadanganEmail = true): string
    {
        $tujuan = $user->no_hp ?: $user->profilWarga?->no_hp;

        return $this->kirim($user, $tujuan, $isiTerkirim, $isiTersimpan ?? $isiTerkirim, $cadanganEmail);
    }

    public function keNomor(string $nomor, string $isiTerkirim, string $isiTersimpan): string
    {
        return $this->kirim(null, $nomor, $isiTerkirim, $isiTersimpan);
    }

    private function kirim(?User $user, ?string $tujuan, string $isiTerkirim, string $isiTersimpan, bool $cadanganEmail = true): string
    {
        $kanal = (string) config('surat.notifikasi.kanal', 'catat');
        $status = 'tertulis';
        $kanalTersimpan = 'aplikasi';

        if ($kanal === 'whatsapp') {
            $status = filled($tujuan) ? $this->whatsapp((string) $tujuan, $isiTerkirim) : 'gagal';
            $kanalTersimpan = 'whatsapp';

            if ($cadanganEmail && $status !== 'terkirim' && filled($user?->email)) {
                $status = $this->email($user->email, $isiTerkirim) ? 'email' : $status;
                $kanalTersimpan = $status === 'email' ? 'email' : 'whatsapp';
            }
        }

        try {
            Notifikasi::query()->create([
                'user_id' => $user?->id,
                'tujuan' => $tujuan,
                'kanal' => $kanalTersimpan,
                'isi' => $isiTersimpan,
                'status_kirim' => $status,
                'waktu' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('notifikasi gagal dicatat', ['pesan' => $e->getMessage()]);
        }

        return $status;
    }

    private function whatsapp(string $tujuan, string $isi): string
    {
        $url = (string) config('surat.notifikasi.whatsapp_url');

        if ($url === '') {
            Log::warning('whatsapp tidak terkirim', ['alasan' => 'WHATSAPP_GATEWAY_URL kosong']);

            return 'gagal';
        }

        try {
            $permintaan = Http::timeout(20)->acceptJson();
            $token = (string) config('surat.notifikasi.whatsapp_token');

            if ($token !== '') {
                $permintaan = $permintaan->withToken($token);
            }

            $jawaban = $permintaan->post($url, [
                'pengirim' => (string) config('surat.notifikasi.nomor_whatsapp'),
                'tujuan' => $tujuan,
                'pesan' => $isi,
            ]);

            return $jawaban->successful() ? 'terkirim' : 'gagal';
        } catch (Throwable) {
            return 'gagal';
        }
    }

    private function email(string $email, string $isi): bool
    {
        try {
            Mail::raw($isi, function ($pesan) use ($email): void {
                $pesan->to($email)->subject('Status layanan surat');
            });

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
