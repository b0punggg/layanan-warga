@extends('layouts.tamu')

@section('judul', 'Informasi publik')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Informasi publik</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-tinta/70">Pengumuman yang diterbitkan kalurahan. Teks contoh bukan pengumuman resmi.</p>
    <div class="mt-6 space-y-3">
        @forelse ($informasi as $item)
            <article class="kartu">
                <h2 class="font-serif text-2xl">{{ $item->judul }}</h2>
                <p class="mt-2 text-sm leading-relaxed">{{ $item->ringkasan }}</p>
                <a href="{{ route('informasi.lihat', $item) }}" class="mt-3 inline-block text-sm font-semibold text-hijau">Baca</a>
            </article>
        @empty
            <p class="kartu text-sm text-tinta/70">Belum ada informasi yang diterbitkan.</p>
        @endforelse
    </div>
@endsection
