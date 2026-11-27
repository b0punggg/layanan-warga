@extends('layouts.aplikasi')

@section('judul', 'Pengaduan masuk')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Pengaduan masuk</h1>
    <div class="mt-6 space-y-3">
        @forelse ($pengaduan as $item)
            <article class="kartu">
                <p class="text-sm text-tanah">ADU-{{ $item->id }} · {{ $item->status->label() }}</p>
                <h2 class="font-serif text-2xl">{{ $item->judul }}</h2>
                <p class="mt-1 text-sm text-tinta/70">{{ $item->nama }} · {{ $item->no_hp }}</p>
                <a href="{{ route('pengaduan.lihat', $item) }}" class="tombol mt-4">Baca</a>
            </article>
        @empty
            <p class="kartu text-sm text-tinta/70">Belum ada pengaduan.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $pengaduan->links() }}</div>
@endsection
