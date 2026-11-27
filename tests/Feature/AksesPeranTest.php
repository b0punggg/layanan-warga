<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use App\Models\JenisLayanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AksesPeranTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get(route('warga.beranda'))->assertRedirect(route('masuk'));
    }

    public function test_setiap_peran_hanya_membuka_berandanya(): void
    {
        $warga = $this->pengguna(Peran::Warga);
        $operator = $this->pengguna(Peran::Operator);
        $lurah = $this->pengguna(Peran::Lurah);
        $admin = $this->pengguna(Peran::Admin);

        $this->actingAs($warga)->get(route('warga.beranda'))->assertOk();
        $this->actingAs($warga)->get(route('admin.beranda'))->assertForbidden();

        $this->actingAs($operator)->get(route('operator.beranda'))->assertOk();
        $this->actingAs($operator)->get(route('lurah.beranda'))->assertForbidden();

        $this->actingAs($lurah)->get(route('lurah.beranda'))->assertOk();
        $this->actingAs($lurah)->get(route('operator.beranda'))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.beranda'))->assertOk();
        $this->actingAs($admin)->get(route('warga.beranda'))->assertForbidden();
    }

    public function test_masuk_dengan_email_mencatat_audit(): void
    {
        $admin = $this->pengguna(Peran::Admin);

        $this->post(route('masuk.store'), [
            'login' => $admin->email,
            'password' => 'rahasia-uji',
        ])->assertRedirect(route('admin.beranda'));

        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseHas('audit_logs', [
            'aksi' => 'masuk',
            'user_id' => $admin->id,
        ]);
        $this->assertNotNull($admin->refresh()->terakhir_login);
    }

    public function test_warga_bisa_masuk_dengan_nik(): void
    {
        $warga = $this->pengguna(Peran::Warga, ['nik' => '3400000000000001']);

        $this->post(route('masuk.store'), [
            'login' => '3400000000000001',
            'password' => 'rahasia-uji',
        ])->assertRedirect(route('warga.beranda'));

        $this->assertAuthenticatedAs($warga);
    }

    public function test_akun_nonaktif_tidak_bisa_masuk(): void
    {
        $user = $this->pengguna(Peran::Operator, ['status_akun' => StatusAkun::Nonaktif]);

        $this->post(route('masuk.store'), [
            'login' => $user->email,
            'password' => 'rahasia-uji',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_admin_menambah_staf_dan_tidak_menonaktifkan_diri(): void
    {
        $admin = $this->pengguna(Peran::Admin);

        $this->actingAs($admin)->post(route('admin.pengguna.store'), [
            'name' => 'Carik Baru',
            'email' => 'carik@kalurahan.local',
            'peran' => Peran::Operator->value,
            'password' => 'kata-sandi-aman',
            'password_confirmation' => 'kata-sandi-aman',
        ])->assertRedirect(route('admin.pengguna.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'carik@kalurahan.local',
            'peran' => Peran::Operator->value,
        ]);

        $this->actingAs($admin)->patch(route('admin.pengguna.status', $admin), [
            'status_akun' => StatusAkun::Nonaktif->value,
        ])->assertSessionHasErrors('status_akun');

        $this->assertSame(StatusAkun::Aktif, $admin->refresh()->status_akun);
    }

    public function test_pratinjau_template_hanya_untuk_admin(): void
    {
        JenisLayanan::query()->create([
            'nama' => 'Surat Keterangan Usaha',
            'kode' => 'SKU',
            'butuh_pengantar_rt' => true,
            'mode_pengantar' => 'unggah',
            'daftar_syarat' => [],
            'skema_field_tambahan' => [],
            'format_nomor' => '400.10.5/{nomor}',
            'template_surat' => 'surat.templates.sku',
            'estimasi_hari' => 3,
            'aktif' => true,
        ]);

        $this->actingAs($this->pengguna(Peran::Admin))
            ->get(route('admin.templat', 'SKU'))
            ->assertOk()
            ->assertSee('SURAT KETERANGAN USAHA')
            ->assertSee('Pemerintah Kalurahan Sindumartani')
            ->assertSee('400.10.5/001')
            ->assertDontSee('belum dikonfirmasi');

        $this->actingAs($this->pengguna(Peran::Warga))
            ->get(route('admin.templat', 'SKU'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function pengguna(Peran $peran, array $atribut = []): User
    {
        return User::factory()->create([
            'peran' => $peran,
            'password' => 'rahasia-uji',
            ...$atribut,
        ]);
    }
}
