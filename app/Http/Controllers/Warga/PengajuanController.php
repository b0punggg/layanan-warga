<?php

namespace App\Http\Controllers\Warga;

use App\Enums\ModePengantar;
use App\Enums\StatusPengajuan;
use App\Enums\StatusPengantarRt;
use App\Http\Controllers\Controller;
use App\Models\BerkasPengajuan;
use App\Models\JenisLayanan;
use App\Models\Pengajuan;
use App\Services\BerkasPrivat;
use App\Services\LengkapiProfil;
use App\Services\PenerbitSurat;
use App\Support\IsianSurat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PengajuanController extends Controller
{
    public function index(): View
    {
        return view('warga.pengajuan.index', [
            'pengajuan' => auth()->user()->pengajuan()->with('jenisLayanan')->latest()->get(),
            'bolehMengajukan' => auth()->user()->bolehMengajukan(),
        ]);
    }

    public function layanan(): View
    {
        abort_unless(auth()->user()->bolehMengajukan(), 403, 'Akun harus disetujui operator sebelum mengajukan surat.');

        return view('warga.pengajuan.layanan', [
            'layanan' => JenisLayanan::query()->where('aktif', true)->orderBy('nama')->get(),
        ]);
    }

    public function buat(JenisLayanan $jenisLayanan): View
    {
        abort_unless(auth()->user()->bolehMengajukan(), 403, 'Akun harus disetujui operator sebelum mengajukan surat.');
        abort_unless($jenisLayanan->aktif, 404);

        return view('warga.pengajuan.formulir', [
            'jenis' => $jenisLayanan,
            'pengajuan' => null,
        ]);
    }

    public function simpan(Request $request, JenisLayanan $jenisLayanan, PenerbitSurat $penerbit, BerkasPrivat $berkas): RedirectResponse
    {
        abort_unless(auth()->user()->bolehMengajukan(), 403);
        abort_unless($jenisLayanan->aktif, 404);
        $draf = $request->input('aksi') !== 'kirim';
        $valid = $this->validasi($request, $jenisLayanan, null, $draf);

        if ($valid->fails()) {
            return back()->withInput()->withErrors($valid);
        }

        app(LengkapiProfil::class)->dariSurat($request->user(), $valid->validated()['data'] ?? []);

        $pengajuan = Pengajuan::query()->create([
            'kode_tiket' => $this->tiket(),
            'user_id' => $request->user()->id,
            'jenis_layanan_id' => $jenisLayanan->id,
            'data_form' => $valid->validated()['data'] ?? [],
            'status' => StatusPengajuan::Draft,
            'status_pengantar_rt' => $jenisLayanan->butuh_pengantar_rt && $jenisLayanan->mode_pengantar === ModePengantar::VerifikasiOnline
                ? StatusPengantarRt::MenungguUnggah
                : StatusPengantarRt::TidakPerlu,
        ]);

        $this->simpanBerkas($request, $pengajuan, $jenisLayanan, $berkas);

        if (! $draf) {
            try {
                $penerbit->kirim($pengajuan->fresh(), $request->user());
            } catch (RuntimeException $e) {
                return back()->withErrors(['aksi' => $e->getMessage()]);
            }
        }

        return redirect()->route('warga.pengajuan.lihat', $pengajuan)->with('status', $draf ? 'Draf tersimpan.' : 'Pengajuan terkirim.');
    }

    public function lihat(Pengajuan $pengajuan): View
    {
        $this->milikSaya($pengajuan);
        $pengajuan->load(['jenisLayanan', 'berkas', 'riwayatStatus.pelaku', 'surat']);

        return view('warga.pengajuan.lihat', ['pengajuan' => $pengajuan]);
    }

    public function perbarui(Request $request, Pengajuan $pengajuan, PenerbitSurat $penerbit, BerkasPrivat $berkas): RedirectResponse
    {
        $this->milikSaya($pengajuan);
        abort_unless(in_array($pengajuan->status, [StatusPengajuan::Draft, StatusPengajuan::PerluPerbaikan], true), 403);

        $draf = $request->input('aksi') !== 'kirim';
        $valid = $this->validasi($request, $pengajuan->jenisLayanan, $pengajuan, $draf);

        if ($valid->fails()) {
            return back()->withInput()->withErrors($valid);
        }

        app(LengkapiProfil::class)->dariSurat($request->user(), $valid->validated()['data'] ?? []);
        $pengajuan->update(['data_form' => $valid->validated()['data'] ?? []]);
        $this->simpanBerkas($request, $pengajuan, $pengajuan->jenisLayanan, $berkas);

        if (! $draf) {
            $penerbit->kirim($pengajuan->fresh(), $request->user());
        }

        return redirect()->route('warga.pengajuan.lihat', $pengajuan)->with('status', $draf ? 'Draf diperbarui.' : 'Pengajuan terkirim.');
    }

    private function milikSaya(Pengajuan $pengajuan): void
    {
        abort_unless($pengajuan->user_id === auth()->id(), 403);
    }

    private function validasi(Request $request, JenisLayanan $jenis, ?Pengajuan $pengajuan, bool $draf): \Illuminate\Validation\Validator
    {
        $aturan = [];
        $pesan = [];

        foreach (app(IsianSurat::class)->baris($jenis) as $field) {
            $kunci = 'data.'.$field['nama'];
            $wajib = ! $draf && ($field['wajib'] ?? false);
            $panjang = in_array($field['nama'], ['alamat', 'alamat_usaha', 'alamat_tujuan'], true) ? 500 : 255;
            $aturan[$kunci] = [$wajib ? 'required' : 'nullable', 'string', 'max:'.$panjang];
            $pilihan = array_column($field['pilihan'] ?? [], 'nilai');
            if ($pilihan !== []) {
                $aturan[$kunci][] = Rule::in($pilihan);
                $pesan[$kunci.'.in'] = $field['label'].' belum dipilih.';
            }
            $pesan[$kunci.'.required'] = $field['label'].' wajib diisi.';
            $pesan[$kunci.'.max'] = $field['label'].' terlalu panjang.';
        }

        foreach ($jenis->daftar_syarat ?? [] as $syarat) {
            $kunci = 'berkas.'.$syarat['nama'];
            $sudahAda = $pengajuan !== null && $pengajuan->berkas()->where('nama_syarat', $syarat['nama'])->exists();
            $wajib = ! $draf && ($syarat['wajib'] ?? false) && ! $sudahAda;
            $aturan[$kunci] = [$wajib ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'];
            $pesan[$kunci.'.required'] = $syarat['label'].' wajib diunggah.';
            $pesan[$kunci.'.mimes'] = $syarat['label'].' harus JPG, PNG, atau PDF.';
            $pesan[$kunci.'.max'] = $syarat['label'].' paling besar 2 MB.';
            $pesan[$kunci.'.uploaded'] = $syarat['label'].' gagal diunggah. Coba berkas yang lebih kecil.';
        }

        return Validator::make($request->all(), $aturan, $pesan);
    }

    private function simpanBerkas(Request $request, Pengajuan $pengajuan, JenisLayanan $jenis, BerkasPrivat $berkas): void
    {
        foreach ($jenis->daftar_syarat ?? [] as $syarat) {
            $file = $request->file('berkas.'.$syarat['nama']);

            if ($file === null) {
                continue;
            }

            $lama = $pengajuan->berkas()->where('nama_syarat', $syarat['nama'])->first();

            if ($lama !== null) {
                $berkas->hapus($lama->path_file);
                $lama->delete();
            }

            BerkasPengajuan::query()->create([
                'pengajuan_id' => $pengajuan->id,
                'nama_syarat' => $syarat['nama'],
                'path_file' => $berkas->simpanUnggahan($file, 'pengajuan/'.$pengajuan->id.'/'.$syarat['nama'].'.'.$file->extension()),
                'tipe' => $file->getMimeType() ?: 'application/octet-stream',
                'ukuran' => $file->getSize(),
                'diunggah_pada' => now(),
            ]);
        }

        if ($request->file('pengantar') !== null) {
            if ($pengajuan->berkas_pengantar_path) {
                $berkas->hapus($pengajuan->berkas_pengantar_path);
            }

            $file = $request->file('pengantar');
            $pengajuan->update([
                'berkas_pengantar_path' => $berkas->simpanUnggahan($file, 'pengantar/'.$pengajuan->id.'.'.$file->extension()),
                'status_pengantar_rt' => StatusPengantarRt::Diunggah,
            ]);
        }
    }

    private function tiket(): string
    {
        do {
            $kode = 'LW-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (Pengajuan::query()->where('kode_tiket', $kode)->exists());

        return $kode;
    }
}
