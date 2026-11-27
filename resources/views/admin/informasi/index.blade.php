@extends('layouts.aplikasi')

@section('judul', 'Informasi')

@section('isi')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h1 class="font-serif text-3xl text-hijau-dalam">Informasi publik</h1>
        <a href="{{ route('admin.informasi.buat') }}" class="tombol">Tulis</a>
    </div>
    @if (session('status'))
        <p class="mt-4 rounded-xl border border-daun/30 bg-white px-4 py-3 text-sm text-hijau">{{ session('status') }}</p>
    @endif
    <div class="mt-6 space-y-3">
        @forelse ($informasi as $item)
            <article class="kartu flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-tanah">{{ $item->terbit ? 'Terbit' : 'Draf' }}</p>
                    <h2 class="font-serif text-2xl">{{ $item->judul }}</h2>
                </div>
                <a href="{{ route('admin.informasi.ubah', $item) }}" class="tombol-tenang">Ubah</a>
            </article>
        @empty
            <p class="kartu text-sm text-tinta/70">Belum ada tulisan.</p>
        @endforelse
    </div>
@endsection
