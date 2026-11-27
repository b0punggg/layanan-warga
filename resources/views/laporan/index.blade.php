@extends('layouts.aplikasi')

@section('judul', 'Laporan')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Rekap pengajuan</h1>
    <form method="GET" action="{{ route('laporan.index') }}" class="mt-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="tahun" class="mb-1 block text-sm font-semibold">Tahun</label>
            <input id="tahun" name="tahun" type="number" value="{{ $tahun }}" class="bidang w-28">
        </div>
        <div>
            <label for="bulan" class="mb-1 block text-sm font-semibold">Bulan</label>
            <input id="bulan" name="bulan" type="number" min="1" max="12" value="{{ $bulan }}" placeholder="Semua" class="bidang w-28">
        </div>
        <button class="tombol" type="submit">Tampilkan</button>
        <a class="tombol-tenang" href="{{ route('laporan.excel', ['tahun' => $tahun, 'bulan' => $bulan]) }}">Excel</a>
        <a class="tombol-tenang" href="{{ route('laporan.pdf', ['tahun' => $tahun, 'bulan' => $bulan]) }}">PDF</a>
    </form>

    <h2 class="mt-8 font-serif text-2xl">Per jenis surat</h2>
    <div class="mt-3 overflow-x-auto rounded-2xl border border-garis bg-lembar">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-garis text-tinta/70">
                <tr>
                    <th class="px-4 py-3 font-semibold">Jenis</th>
                    <th class="px-4 py-3 font-semibold">Diajukan</th>
                    <th class="px-4 py-3 font-semibold">Selesai</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($per_jenis as $nama => $angka)
                    <tr class="border-b border-garis last:border-0">
                        <td class="px-4 py-3">{{ $nama }}</td>
                        <td class="px-4 py-3">{{ $angka['jumlah'] }}</td>
                        <td class="px-4 py-3">{{ $angka['selesai'] }}</td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-3 text-tinta/70" colspan="3">Belum ada pengajuan pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="mt-8 font-serif text-2xl">Per dukuh</h2>
    <div class="mt-3 overflow-x-auto rounded-2xl border border-garis bg-lembar">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-garis text-tinta/70">
                <tr>
                    <th class="px-4 py-3 font-semibold">Dukuh</th>
                    <th class="px-4 py-3 font-semibold">Diajukan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($per_dukuh as $dukuh => $jumlah)
                    <tr class="border-b border-garis last:border-0">
                        <td class="px-4 py-3">{{ $dukuh }}</td>
                        <td class="px-4 py-3">{{ $jumlah }}</td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-3 text-tinta/70" colspan="2">Belum ada pengajuan pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
