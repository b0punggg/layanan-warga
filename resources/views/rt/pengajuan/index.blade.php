@extends('layouts.aplikasi')

@section('judul', 'Pengantar RT/RW')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Pengantar menunggu</h1>
    <p class="mt-2 text-sm text-tinta/70">RT {{ auth()->user()->rt }} RW {{ auth()->user()->rw }}@if (auth()->user()->dukuh), Dukuh {{ auth()->user()->dukuh }}@endif.</p>
    @if (session('status'))
        <p class="mt-3 rounded-xl border border-daun/30 bg-white px-4 py-3 text-sm text-hijau">{{ session('status') }}</p>
    @endif
    <div class="mt-6 space-y-3">
        @forelse ($pengajuan as $item)
            <article class="kartu">
                <p class="text-sm text-tanah">{{ $item->kode_tiket }}</p>
                <h2 class="font-serif text-2xl">{{ $item->pemohon->name }}</h2>
                <p class="mt-1 text-sm text-tinta/70">{{ $item->jenisLayanan->nama }}</p>
                <a href="{{ route('rt.pengajuan.lihat', $item) }}" class="tombol mt-4">Tinjau</a>
            </article>
        @empty
            <p class="kartu text-sm text-tinta/70">Tidak ada pengantar yang menunggu di wilayah ini.</p>
        @endforelse
    </div>
@endsection
