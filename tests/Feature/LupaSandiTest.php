<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LupaSandiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tautan_dikirim_ke_email_dan_whatsapp_tanpa_disimpan(): void
    {
        Http::fake(['https://gateway.test/*' => Http::response(['ok' => true])]);
        config([
            'surat.notifikasi.kanal' => 'whatsapp',
            'surat.notifikasi.whatsapp_url' => 'https://gateway.test/kirim',
            'surat.notifikasi.nomor_whatsapp' => '0881080712007',
        ]);

        User::factory()->create([
            'email' => 'warga@contoh.test',
            'no_hp' => '081234567890',
            'peran' => Peran::Warga,
            'status_akun' => StatusAkun::Aktif,
        ]);

        $this->post(route('sandi.kirim'), ['email' => 'tidak-ada@contoh.test'])
            ->assertSessionHas('status', 'Jika email terdaftar, tautan pengaturan kata sandi dikirim ke email itu dan ke WhatsApp nomor akun tersebut.');
        Http::assertNothingSent();

        $this->post(route('sandi.kirim'), ['email' => 'warga@contoh.test'])
            ->assertSessionHas('status');

        $token = null;
        Http::assertSent(function ($permintaan) use (&$token) {
            $pesan = (string) $permintaan['pesan'];
            if (! preg_match('#/sandi/atur/([^?\s.]+)#', $pesan, $cocok)) {
                return false;
            }
            $token = $cocok[1];

            return $permintaan['tujuan'] === '081234567890'
                && $permintaan['pengirim'] === '0881080712007';
        });

        $this->assertNotNull($token);
        $this->assertStringNotContainsString($token, (string) Notifikasi::query()->value('isi'));

        $surat = app(MailManager::class)->mailer()->getSymfonyTransport()->messages()->first();
        $this->assertNotNull($surat);
        $this->assertStringContainsString($token, $surat->getOriginalMessage()->toString());
        $this->assertStringContainsString('warga@contoh.test', $surat->getOriginalMessage()->toString());

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'warga@contoh.test',
            'password' => 'sandi-baru-aman',
            'password_confirmation' => 'sandi-baru-aman',
        ])->assertRedirect(route('masuk'));

        $this->post(route('masuk'), [
            'login' => 'warga@contoh.test',
            'password' => 'sandi-baru-aman',
        ])->assertRedirect(route('warga.beranda'));
    }
}
