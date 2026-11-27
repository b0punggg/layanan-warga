@extends('layouts.tamu')

@section('judul', $informasi->judul)

@section('isi')
    <p class="text-sm text-tinta/60">{{ $informasi->diterbitkan_pada?->translatedFormat('d F Y') }}</p>
    <h1 class="mt-2 font-serif text-3xl text-hijau-dalam">{{ $informasi->judul }}</h1>
    <div class="mt-4 max-w-2xl space-y-3 text-sm leading-relaxed">
        @foreach (preg_split("/\n{2,}/", $informasi->isi) ?: [] as $paragraf)
            <p>{!! nl2br(e(trim($paragraf))) !!}</p>
        @endforeach
    </div>
    <a href="{{ route('informasi.index') }}" class="tombol-tenang mt-6">Kembali</a>
@endsection
