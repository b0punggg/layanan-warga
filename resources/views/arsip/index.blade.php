@extends('layouts.aplikasi')

@section('judul', 'Arsip')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Buku agenda</h1>
    <form method="GET" class="mt-4 flex gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Nomor surat atau nama" class="bidang">
        <button class="tombol" type="submit">Cari</button>
    </form>
    <div class="mt-4 space-y-2">
        @forelse ($surat as $item)
            <article class="kartu flex flex-wrap items-center justify-between gap-3 text-sm">
                <div>
                    <p class="font-semibold">{{ $item->nomor_surat }}</p>
                    <p class="text-tinta/70">{{ $item->pengajuan->pemohon->name }} · {{ $item->pengajuan->jenisLayanan->nama }}</p>
                </div>
                @if ($item->file_pdf)
                    <a class="font-semibold text-hijau" href="{{ route('berkas.surat', $item) }}">Buka PDF</a>
                @else
                    <span class="text-tinta/70">Belum terbit</span>
                @endif
            </article>
        @empty
            <p class="kartu text-sm text-tinta/70">Belum ada surat di arsip.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $surat->links() }}</div>
@endsection
