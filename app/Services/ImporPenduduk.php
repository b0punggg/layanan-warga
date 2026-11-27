<?php

namespace App\Services;

use App\Enums\StatusDataPenduduk;
use App\Models\Penduduk;
use App\Models\RiwayatImpor;
use App\Models\User;

class ImporPenduduk
{
    public function __construct(private PenguraiAlamat $pengurai) {}

    /**
     * @param  list<array{baris: int, nama: string, nik: string, alamat: string}>  $baris
     * @return array{baris: list<array<string, mixed>>, tambah: int, perbarui: int, gagal: int}
     */
    public function periksa(array $baris): array
    {
        $nikBerkas = [];
        $nikAda = Penduduk::query()
            ->whereIn('nik', collect($baris)->pluck('nik')->filter()->all())
            ->pluck('nik')
            ->all();
        $nikAda = array_fill_keys($nikAda, true);

        $hasil = [];
        $tambah = 0;
        $perbarui = 0;
        $gagal = 0;

        foreach ($baris as $rekam) {
            $alasan = $this->alasan($rekam, $nikBerkas);
            $nikBerkas[$rekam['nik']] = true;
            $urai = $this->pengurai->uraikan($rekam['alamat']);
            $mode = isset($nikAda[$rekam['nik']]) ? 'perbarui' : 'tambah';

            if ($alasan !== []) {
                $gagal++;
                $mode = 'gagal';
            } elseif ($mode === 'perbarui') {
                $perbarui++;
            } else {
                $tambah++;
            }

            $hasil[] = [
                ...$rekam,
                'mode' => $mode,
                'alasan' => $alasan,
                'dukuh' => $urai['dukuh'],
                'rt' => $urai['rt'],
                'rw' => $urai['rw'],
                'alamat_terurai' => $urai['berhasil'],
            ];
        }

        return [
            'baris' => $hasil,
            'tambah' => $tambah,
            'perbarui' => $perbarui,
            'gagal' => $gagal,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $hasil
     */
    public function simpan(array $hasil, User $pelaku, string $namaBerkas): RiwayatImpor
    {
        $sukses = 0;
        $gagal = 0;
        $laporan = [];

        foreach ($hasil as $baris) {
            if ($baris['mode'] === 'gagal') {
                $gagal++;
                $laporan[] = $baris;

                continue;
            }

            $data = [
                'nama' => $baris['nama'],
                'alamat_asli' => $baris['alamat'],
                'sumber_data' => 'impor',
                'tanggal_impor' => now()->toDateString(),
                'status_data' => $baris['alamat_terurai'] ? StatusDataPenduduk::Terverifikasi : StatusDataPenduduk::Menunggu,
            ];

            if ($baris['alamat_terurai']) {
                $data['dukuh'] = $baris['dukuh'];
                $data['rt'] = $baris['rt'];
                $data['rw'] = $baris['rw'];
            }

            $penduduk = Penduduk::query()->where('nik', $baris['nik'])->first();

            if ($penduduk === null) {
                Penduduk::query()->create(['nik' => $baris['nik'], ...$data]);
            } else {
                $penduduk->update($data);
            }

            $sukses++;
        }

        $riwayat = RiwayatImpor::query()->create([
            'nama_file' => $namaBerkas,
            'user_id' => $pelaku->id,
            'waktu' => now(),
            'baris_total' => count($hasil),
            'baris_sukses' => $sukses,
            'baris_gagal' => $gagal,
        ]);

        Penduduk::query()->whereIn('nik', collect($hasil)->where('mode', '!=', 'gagal')->pluck('nik'))->update([
            'riwayat_impor_id' => $riwayat->id,
        ]);

        if ($laporan !== []) {
            $path = 'impor/gagal-'.$riwayat->id.'.csv';
            app(BerkasPrivat::class)->simpan($this->csvGagal($laporan), $path);
            $riwayat->update(['laporan_gagal_path' => $path]);
        }

        AuditLogger::catat('impor_penduduk', 'riwayat_impor:'.$riwayat->id, [
            'sukses' => $sukses,
            'gagal' => $gagal,
        ], $pelaku->id);

        return $riwayat;
    }

    /**
     * @param  array{nama: string, nik: string, alamat: string}  $rekam
     * @param  array<string, bool>  $nikBerkas
     * @return list<string>
     */
    private function alasan(array $rekam, array $nikBerkas): array
    {
        $alasan = [];

        if ($rekam['nama'] === '') {
            $alasan[] = 'Nama kosong';
        }

        if (preg_match('/^\d{16}$/', $rekam['nik']) !== 1) {
            $alasan[] = 'NIK harus 16 digit';
        } elseif (isset($nikBerkas[$rekam['nik']])) {
            $alasan[] = 'NIK ganda di dalam berkas';
        }

        if ($rekam['alamat'] === '') {
            $alasan[] = 'Alamat kosong';
        }

        return $alasan;
    }

    /**
     * @param  list<array<string, mixed>>  $baris
     */
    public function csvGagal(array $baris): string
    {
        $pegangan = fopen('php://temp', 'r+');
        fputcsv($pegangan, ['baris', 'nik', 'nama', 'alamat', 'alasan']);

        foreach ($baris as $rekam) {
            fputcsv($pegangan, [
                $rekam['baris'],
                $rekam['nik'],
                $rekam['nama'],
                $rekam['alamat'],
                implode('; ', $rekam['alasan']),
            ]);
        }

        rewind($pegangan);
        $isi = stream_get_contents($pegangan) ?: '';
        fclose($pegangan);

        return $isi;
    }
}
