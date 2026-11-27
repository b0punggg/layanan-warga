<?php

namespace App\Services;

use App\Enums\StatusPengajuan;
use App\Models\Pengajuan;
use Illuminate\Support\Collection;

class RekapLayanan
{
    /**
     * @return array{tahun: int, bulan: ?int, per_jenis: Collection<string, array{jumlah: int, selesai: int}>, per_dukuh: Collection<string, int>}
     */
    public function susun(int $tahun, ?int $bulan): array
    {
        $baris = Pengajuan::query()
            ->with(['jenisLayanan', 'pemohon.profilWarga'])
            ->whereYear('tanggal_ajuan', $tahun)
            ->when($bulan, fn ($query) => $query->whereMonth('tanggal_ajuan', $bulan))
            ->get();

        $perJenis = $baris
            ->groupBy(fn (Pengajuan $pengajuan) => $pengajuan->jenisLayanan->nama)
            ->map(fn (Collection $grup) => [
                'jumlah' => $grup->count(),
                'selesai' => $grup->where('status', StatusPengajuan::Selesai)->count(),
            ]);

        $perDukuh = $baris
            ->groupBy(fn (Pengajuan $pengajuan) => $pengajuan->pemohon->profilWarga->dukuh ?: 'Belum tercatat')
            ->map(fn (Collection $grup) => $grup->count());

        return [
            'tahun' => $tahun,
            'bulan' => $bulan,
            'per_jenis' => $perJenis,
            'per_dukuh' => $perDukuh,
        ];
    }
}
