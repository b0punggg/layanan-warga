@extends('layouts.aplikasi')

@section('judul', 'Template surat')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Template surat</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-tinta/70">Contoh tiap jenis surat. Berkas Word memakai data contoh dan nomor urut 001. Itu bukan surat yang sudah terbit.</p>
    <div class="mt-6 space-y-3">
        @forelse ($layanan as $item)
            <article class="kartu flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-tanah">{{ $item->kode }}</p>
                    <h2 class="font-serif text-2xl">{{ $item->nama }}</h2>
                    <p class="text-sm text-tinta/70">{{ $item->isianTerkunci() ? 'Isian terkunci' : 'Isian bisa diubah' }}</p>
                    @unless ($item->aktif)
                        <p class="text-sm font-semibold text-tanah">Nonaktif</p>
                    @endunless
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('operator.templat.lihat', $item) }}" class="tombol-tenang">Lihat</a>
                    <a href="{{ route('operator.templat.word', $item) }}" class="tombol">Unduh Word</a>
                </div>
            </article>
        @empty
            <p class="kartu text-sm text-tinta/70">Belum ada jenis surat.</p>
        @endforelse
    </div>
@endsection
