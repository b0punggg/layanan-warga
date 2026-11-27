<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Models\BerkasPengajuan;
use App\Models\Pengajuan;
use App\Models\ProfilWarga;
use App\Models\Surat;
use App\Services\AuditLogger;
use App\Services\BerkasPrivat;
use Illuminate\Http\Response;

class BerkasController extends Controller
{
    public function __construct(private BerkasPrivat $berkas) {}

    public function syarat(BerkasPengajuan $berkas): Response
    {
        $this->bolehPengajuan($berkas->pengajuan);
        AuditLogger::catat('buka_berkas', 'berkas_pengajuan:'.$berkas->id);

        return $this->tampil($berkas->path_file, $berkas->tipe);
    }

    public function pengantar(Pengajuan $pengajuan): Response
    {
        $this->bolehPengajuan($pengajuan);
        abort_unless($pengajuan->berkas_pengantar_path, 404);
        AuditLogger::catat('buka_berkas', 'pengantar:'.$pengajuan->id);

        return $this->tampil($pengajuan->berkas_pengantar_path, 'application/octet-stream');
    }

    public function identitas(ProfilWarga $profil, string $jenis): Response
    {
        abort_unless(in_array($jenis, ['ktp', 'selfie'], true), 404);
        $this->bolehIdentitas($profil, $jenis);
        $path = $jenis === 'ktp' ? $profil->foto_ktp_path : $profil->foto_selfie_path;
        abort_unless($path, 404);
        AuditLogger::catat('buka_berkas_identitas', 'profil_warga:'.$profil->id.':'.$jenis);

        return $this->tampil($path, 'image/jpeg');
    }

    public function surat(Surat $surat): Response
    {
        $this->bolehPengajuan($surat->pengajuan);
        abort_unless($surat->file_pdf, 404);

        return $this->tampil($surat->file_pdf, 'application/pdf');
    }

    public function format(Pengajuan $pengajuan): Response
    {
        $this->bolehPengajuan($pengajuan);
        abort_unless($pengajuan->file_hasil, 404);

        return response($this->berkas->baca($pengajuan->file_hasil), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.str($pengajuan->kode_tiket)->slug().'-format.docx"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function bolehPengajuan(Pengajuan $pengajuan): void
    {
        $user = auth()->user();
        $staf = in_array($user->peran, [Peran::Operator, Peran::Lurah, Peran::Admin], true);
        abort_unless($staf || $pengajuan->user_id === $user->id, 403);
    }

    private function bolehIdentitas(ProfilWarga $profil, string $jenis): void
    {
        $user = auth()->user();

        if ($jenis === 'selfie') {
            abort_unless(in_array($user->peran, [Peran::Operator, Peran::Admin], true), 403);

            return;
        }

        $staf = in_array($user->peran, [Peran::Operator, Peran::Lurah, Peran::Admin], true);
        abort_unless($staf || $profil->user_id === $user->id, 403);
    }

    private function tampil(string $path, string $tipe): Response
    {
        $isi = $this->berkas->baca($path);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($isi) ?: $tipe;

        return response($isi, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="berkas"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
