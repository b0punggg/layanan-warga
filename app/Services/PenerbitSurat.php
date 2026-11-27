<?php

namespace App\Services;

use App\Enums\ModePengantar;
use App\Enums\StatusPengajuan;
use App\Enums\StatusPengantarRt;
use App\Models\PejabatKalurahan;
use App\Models\Pengajuan;
use App\Models\PenghitungNomor;
use App\Models\RiwayatStatus;
use App\Models\Surat;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PenerbitSurat
{
    public function __construct(
        private NomorSurat $nomorSurat,
        private BerkasPrivat $berkas,
        private PengirimPesan $pesan,
        private PenyusunNaskah $naskah,
    ) {}

    public function kirim(Pengajuan $pengajuan, User $pelaku): void
    {
        if (! in_array($pengajuan->status, [StatusPengajuan::Draft, StatusPengajuan::PerluPerbaikan], true)) {
            throw new RuntimeException('Pengajuan ini tidak dapat dikirim.');
        }

        $pengajuan->loadMissing('jenisLayanan');

        if ($pengajuan->jenisLayanan->butuh_pengantar_rt && $pengajuan->jenisLayanan->mode_pengantar === ModePengantar::VerifikasiOnline) {
            $pengajuan->update(['status_pengantar_rt' => StatusPengantarRt::MenungguRt]);
        }

        $this->ubahStatus($pengajuan, StatusPengajuan::Diajukan, $pelaku, null);
        $pengajuan->update(['tanggal_ajuan' => $pengajuan->tanggal_ajuan ?? now()]);
    }

    public function setujuiPengantar(Pengajuan $pengajuan, User $rt): void
    {
        if ($pengajuan->status !== StatusPengajuan::Diajukan || $pengajuan->status_pengantar_rt !== StatusPengantarRt::MenungguRt) {
            throw new RuntimeException('Pengantar ini tidak menunggu persetujuan RT/RW.');
        }

        $pengajuan->update(['status_pengantar_rt' => StatusPengantarRt::DisetujuiRt]);
        $pengajuan->loadMissing(['pemohon', 'jenisLayanan']);
        $this->pesan->keAkun($pengajuan->pemohon, 'Pengantar RT/RW untuk '.$pengajuan->kode_tiket.' disetujui. Pengajuan menunggu operator.');
        AuditLogger::catat('setujui_pengantar_rt', 'pengajuan:'.$pengajuan->id, null, $rt->id);
    }

    public function tolakPengantar(Pengajuan $pengajuan, User $rt, string $catatan): void
    {
        if ($pengajuan->status !== StatusPengajuan::Diajukan || $pengajuan->status_pengantar_rt !== StatusPengantarRt::MenungguRt) {
            throw new RuntimeException('Pengantar ini tidak menunggu persetujuan RT/RW.');
        }

        $pengajuan->update(['status_pengantar_rt' => StatusPengantarRt::DitolakRt]);
        $this->mintaPerbaikan($pengajuan, $rt, $catatan);
        AuditLogger::catat('tolak_pengantar_rt', 'pengajuan:'.$pengajuan->id, null, $rt->id);
    }

    public function mintaPerbaikan(Pengajuan $pengajuan, User $pelaku, string $catatan): void
    {
        if ($pengajuan->status !== StatusPengajuan::Diajukan) {
            throw new RuntimeException('Hanya pengajuan yang baru masuk yang dapat dikembalikan.');
        }

        $this->ubahStatus($pengajuan, StatusPengajuan::PerluPerbaikan, $pelaku, $catatan);
    }

    public function tolak(Pengajuan $pengajuan, User $pelaku, string $catatan): void
    {
        $boleh = ($pelaku->peran->value === 'lurah' && $pengajuan->status === StatusPengajuan::Diverifikasi)
            || ($pelaku->peran->value === 'operator' && $pengajuan->status === StatusPengajuan::Diajukan);

        if (! $boleh) {
            throw new RuntimeException('Pengajuan ini tidak dapat ditolak dari langkah saat ini.');
        }

        $this->ubahStatus($pengajuan, StatusPengajuan::Ditolak, $pelaku, $catatan);
    }

    public function usulan(Pengajuan $pengajuan): string
    {
        $pengajuan->loadMissing('jenisLayanan');
        $terakhir = (int) PenghitungNomor::query()->where('tahun', (int) now()->year)->value('terakhir');

        return $this->nomorSurat->susun(
            $pengajuan->jenisLayanan->kode,
            $terakhir + 1,
            now(),
            $pengajuan->jenisLayanan->format_nomor,
        );
    }

    public function kirimKeLurah(Pengajuan $pengajuan, User $operator, ?string $nomorPilihan = null): Surat
    {
        return DB::transaction(function () use ($pengajuan, $operator, $nomorPilihan) {
            $pengajuan->refresh();

            if ($pengajuan->status !== StatusPengajuan::Diajukan) {
                throw new RuntimeException('Pengajuan belum siap diajukan ke Lurah.');
            }

            $pengajuan->loadMissing('jenisLayanan');

            if ($pengajuan->jenisLayanan->pernyataan) {
                throw new RuntimeException('Surat pernyataan dikirim langsung ke pemohon, tanpa Lurah.');
            }

            if ($pengajuan->jenisLayanan->butuh_pengantar_rt
                && $pengajuan->jenisLayanan->mode_pengantar === ModePengantar::VerifikasiOnline
                && $pengajuan->status_pengantar_rt !== StatusPengantarRt::DisetujuiRt) {
                throw new RuntimeException('Pengantar RT/RW belum disetujui.');
            }

            $surat = $pengajuan->surat ?? $this->alokasi($pengajuan, $nomorPilihan);
            $this->ubahStatus($pengajuan, StatusPengajuan::Diverifikasi, $operator, null);

            return $surat;
        });
    }

    public function setujui(Pengajuan $pengajuan, User $lurah): void
    {
        DB::transaction(function () use ($pengajuan, $lurah) {
            $pengajuan->refresh();

            if ($pengajuan->status !== StatusPengajuan::Diverifikasi || $pengajuan->surat === null) {
                throw new RuntimeException('Surat belum siap disetujui.');
            }

            $pejabat = PejabatKalurahan::query()->where('aktif', true)->where('user_id', $lurah->id)->first()
                ?? PejabatKalurahan::query()->where('aktif', true)->first();

            $pengajuan->surat->update([
                'kode_verifikasi' => $pengajuan->surat->kode_verifikasi ?: Str::lower(Str::random(32)),
                'tanggal_terbit' => $pengajuan->surat->tanggal_terbit ?? now()->toDateString(),
                'penandatangan_id' => $pejabat?->id,
            ]);

            $this->ubahStatus($pengajuan, StatusPengajuan::Disetujui, $lurah, null);
        });
    }

    public function terbitkan(Pengajuan $pengajuan, User $operator, string $pdf, ?string $pesan = null): void
    {
        DB::transaction(function () use ($pengajuan, $operator, $pdf, $pesan) {
            $pengajuan->refresh();

            if ($pengajuan->status !== StatusPengajuan::Disetujui || $pengajuan->surat === null) {
                throw new RuntimeException('Surat belum disetujui Lurah.');
            }

            if (! str_starts_with($pdf, '%PDF')) {
                throw new RuntimeException('Berkas harus berupa PDF yang sudah ditandatangani.');
            }

            $path = 'surat/'.$pengajuan->id.'.pdf';
            $this->berkas->simpan($pdf, $path);
            $pengajuan->surat->update(['file_pdf' => $path]);
            $pengajuan->update(['tanggal_selesai' => now()]);
            $this->ubahStatus($pengajuan, StatusPengajuan::Selesai, $operator, $this->pesanBersih($pesan));
        });
    }

    public function kirimFormat(Pengajuan $pengajuan, User $operator, string $berkas, string $pesan): void
    {
        DB::transaction(function () use ($pengajuan, $operator, $berkas, $pesan) {
            $pengajuan->refresh();
            $pengajuan->loadMissing('jenisLayanan');

            if ($pengajuan->status !== StatusPengajuan::Diajukan || ! $pengajuan->jenisLayanan->pernyataan) {
                throw new RuntimeException('Format ini belum siap dikirim ke pemohon.');
            }

            if (! str_starts_with($berkas, 'PK')) {
                throw new RuntimeException('Berkas format tidak dapat dibuat.');
            }

            $path = 'format/'.$pengajuan->id.'.docx';
            $this->berkas->simpan($berkas, $path);
            $pengajuan->update([
                'file_hasil' => $path,
                'tanggal_selesai' => now(),
            ]);
            $this->ubahStatus($pengajuan, StatusPengajuan::Selesai, $operator, $this->pesanBersih($pesan));
        });
    }

    private function pesanBersih(?string $pesan): ?string
    {
        $pesan = trim((string) $pesan);

        return $pesan === '' ? null : $pesan;
    }

    public function pdf(Pengajuan $pengajuan, ?PejabatKalurahan $pejabat = null): string
    {
        $pengajuan->loadMissing(['jenisLayanan', 'pemohon.profilWarga.penduduk', 'surat.penandatangan']);
        $pejabat ??= $pengajuan->surat?->penandatangan;

        $nomor = $pengajuan->surat?->nomor_surat ?? '………………';
        $contoh = $this->dataWarga($pengajuan);
        $html = view('surat.cetak', [
            'template' => $pengajuan->jenisLayanan->tampilanSurat(),
            'nomor' => $nomor,
            'contoh' => $contoh,
            'badan' => $this->naskah->html($pengajuan->jenisLayanan->naskah, array_merge($contoh, ['nomor' => $nomor])),
            'ttdDataUri' => null,
            'pejabatNama' => $pejabat?->nama ?? config('surat.nama_lurah'),
            'tanggalSurat' => ($pengajuan->surat?->tanggal_terbit ?? now())->translatedFormat('d F Y'),
        ])->render();

        $opsi = new Options;
        $opsi->set('isRemoteEnabled', false);
        $opsi->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($opsi);
        $dompdf->loadHtml($html);
        // F4: 215,9 mm × 330,2 mm = 612 × 936 pt.
        $dompdf->setPaper([0, 0, 612.0, 936.0]);
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * @return array<string, string>
     */
    public function dataWarga(Pengajuan $pengajuan): array
    {
        $profil = $pengajuan->pemohon->profilWarga;
        $penduduk = $profil?->penduduk;
        $form = $pengajuan->data_form ?? [];
        $tanggalLahir = $profil?->tanggal_lahir?->translatedFormat('d F Y') ?? '—';

        $data = [
            'nama' => $penduduk?->nama ?? $pengajuan->pemohon->name,
            'nik' => (string) ($pengajuan->pemohon->nik ?? $penduduk?->nik ?? '—'),
            'tempat_lahir' => $profil?->tempat_lahir ?? '—',
            'tanggal_lahir' => $tanggalLahir,
            'jenis_kelamin' => $profil?->jenis_kelamin ?? '—',
            'warga_negara' => 'Indonesia',
            'agama' => $profil?->agama ?? '—',
            'pekerjaan' => $profil?->pekerjaan ?? '—',
            'alamat' => $profil?->alamat_lengkap ?? $penduduk?->alamat_asli ?? '—',
            'nama_usaha' => (string) ($form['nama_usaha'] ?? '—'),
            'jenis_usaha' => (string) ($form['jenis_usaha'] ?? '—'),
            'alamat_usaha' => (string) ($form['alamat_usaha'] ?? '—'),
            'keperluan' => (string) ($form['keperluan'] ?? '—'),
            'jumlah_penghasilan' => (string) ($form['jumlah_penghasilan'] ?? '—'),
        ];

        foreach ($form as $kunci => $nilai) {
            if (is_string($kunci) && preg_match('/^[a-z0-9_]+$/', $kunci) === 1 && is_scalar($nilai) && (string) $nilai !== '') {
                $data[$kunci] = (string) $nilai;
            }
        }

        return $data;
    }

    private function alokasi(Pengajuan $pengajuan, ?string $nomorPilihan = null): Surat
    {
        $tahun = (int) now()->year;
        $pengajuan->loadMissing('jenisLayanan');

        DB::table('penghitung_nomor')->insertOrIgnore([
            'tahun' => $tahun,
            'terakhir' => 0,
        ]);

        $penghitung = PenghitungNomor::query()->where('tahun', $tahun)->lockForUpdate()->first();
        $berikutnya = $penghitung->terakhir + 1;
        $format = $pengajuan->jenisLayanan->format_nomor;
        $nomor = trim((string) $nomorPilihan);
        $urut = $berikutnya;

        if ($nomor === '') {
            $nomor = $this->nomorSurat->susun($pengajuan->jenisLayanan->kode, $berikutnya, now(), $format);
        } else {
            if (Surat::query()->where('nomor_surat', $nomor)->exists()) {
                throw new RuntimeException('Nomor surat ini sudah dipakai.');
            }

            $urut = $this->nomorSurat->urutDariTeks($nomor, $format) ?? $berikutnya;

            if (Surat::query()->where('tahun', $tahun)->where('nomor_urut', $urut)->exists()) {
                throw new RuntimeException('Nomor urut '.$urut.' pada tahun '.$tahun.' sudah dipakai.');
            }
        }

        $penghitung->update(['terakhir' => max($penghitung->terakhir, $urut)]);

        return Surat::query()->create([
            'pengajuan_id' => $pengajuan->id,
            'nomor_surat' => $nomor,
            'tahun' => $tahun,
            'nomor_urut' => $urut,
            'kode_verifikasi' => Str::lower(Str::random(32)),
        ]);
    }

    private function ubahStatus(Pengajuan $pengajuan, StatusPengajuan $baru, User $pelaku, ?string $catatan, bool $beritahu = true): void
    {
        $lama = $pengajuan->status;
        $pengajuan->update(['status' => $baru]);

        RiwayatStatus::query()->create([
            'pengajuan_id' => $pengajuan->id,
            'status_lama' => $lama->value,
            'status_baru' => $baru->value,
            'catatan' => $catatan,
            'user_id' => $pelaku->id,
            'waktu' => now(),
        ]);

        AuditLogger::catat('ubah_status_pengajuan', 'pengajuan:'.$pengajuan->id, [
            'dari' => $lama->value,
            'menjadi' => $baru->value,
        ], $pelaku->id);

        if ($beritahu) {
            $this->beritahu($pengajuan, $baru, $catatan);
        }
    }

    private function beritahu(Pengajuan $pengajuan, StatusPengajuan $baru, ?string $catatan): void
    {
        $pengajuan->loadMissing(['pemohon', 'jenisLayanan']);
        $isi = 'Pengajuan '.$pengajuan->kode_tiket.' ('.$pengajuan->jenisLayanan->nama.') sekarang berstatus '.$baru->label().'.';

        if ($baru === StatusPengajuan::Diajukan && $pengajuan->status_pengantar_rt === StatusPengantarRt::MenungguRt) {
            $isi .= ' Menunggu persetujuan RT/RW.';
        }

        if ($baru === StatusPengajuan::Disetujui) {
            $isi .= ' Surat menyusul setelah ditandatangani.';
        }

        if ($baru === StatusPengajuan::Selesai) {
            $isi .= $pengajuan->jenisLayanan->pernyataan
                ? ' Format surat dapat diunduh di aplikasi.'
                : ' Surat dapat diunduh di aplikasi.';
        }

        if (filled($catatan)) {
            $isi .= ' Catatan: '.$catatan;
        }

        $this->pesan->keAkun($pengajuan->pemohon, $isi);
    }
}
