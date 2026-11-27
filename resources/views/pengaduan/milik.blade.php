@extends('layouts.aplikasi')

@section('judul', 'Aduan saya')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Aduan saya</h1>
    <a href="{{ route('pengaduan.buat') }}" class="tombol mt-4">Buat aduan</a>
    <div class="mt-6 space-y-3">
        @forelse ($pengaduan as $item)
            <article class="kartu">
                <p class="text-sm text-tanah">ADU-{{ $item->id }} · {{ $item->status->label() }}</p>
                <h2 class="font-serif text-2xl">{{ $item->judul }}</h2>
                <p class="mt-2 text-sm leading-relaxed">{{ $item->isi }}</p>
                @if ($item->tanggapan)
                    <p class="mt-3 text-sm leading-relaxed">Tanggapan: {{ $item->tanggapan }}</p>
                @endif
            </article>
        @empty
            <p class="kartu text-sm text-tinta/70">Belum ada aduan dari akun ini.</p>
        @endforelse
    </div>
@endsection
