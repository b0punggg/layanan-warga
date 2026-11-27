@extends('layouts.aplikasi')

@section('judul', 'Penduduk')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Data penduduk</h1>
    <form method="GET" class="mt-4 flex gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Nama atau NIK" class="bidang">
        <button class="tombol" type="submit">Cari</button>
    </form>
    <div class="mt-4 overflow-x-auto rounded-2xl border border-garis bg-lembar">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-garis text-tinta/70"><tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">NIK</th><th class="px-4 py-3">Dukuh</th><th class="px-4 py-3"></th></tr></thead>
            <tbody>
                @forelse ($penduduk as $orang)
                    <tr class="border-b border-garis">
                        <td class="px-4 py-3 font-semibold">{{ $orang->nama }}</td>
                        <td class="px-4 py-3">{{ $orang->nik }}</td>
                        <td class="px-4 py-3">{{ $orang->dukuh ?? 'Belum terurai' }} {{ $orang->rt }}/{{ $orang->rw }}</td>
                        <td class="px-4 py-3"><a class="font-semibold text-hijau" href="{{ route('penduduk.ubah', $orang) }}">Ubah</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-tinta/70">Data tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $penduduk->links() }}</div>
@endsection
