@extends('layouts.aplikasi')

@section('judul', 'Pilih surat')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Pilih jenis surat</h1>
    <div class="mt-4 space-y-3">
        @foreach ($layanan as $item)
            @php
                $ikon = match ($item->kode) {
                    'SKU' => 'usaha',
                    'DOM' => 'rumah',
                    'SKTM' => 'hati',
                    'SKKES' => 'sehat',
                    'PHSL' => 'uang',
                    'SKCK' => 'perisai',
                    'NIKAH' => 'cincin',
                    'LAHIR' => 'bintang',
                    'MATI' => 'arsip',
                    'PINDAH' => 'pindah',
                    default => 'surat',
                };
            @endphp
            <article class="kartu flex flex-col gap-4 sm:flex-row sm:items-start">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-pasir text-hijau">@include('komponen.ikon', ['nama' => $ikon, 'kelas' => 'h-5 w-5'])</span>
                <div>
                    <h2 class="font-serif text-2xl">{{ $item->nama }}</h2>
                    <p class="mt-1 text-sm text-tinta/70">{{ $item->deskripsi }} Perkiraan {{ $item->estimasi_hari }} hari.</p>
                    <p class="mt-2 text-sm">Syarat: {{ collect($item->daftar_syarat)->map(fn ($syarat) => $syarat['label'].(empty($syarat['wajib']) ? ' (opsional)' : ''))->join(', ') }}@if ($item->butuh_pengantar_rt && $item->mode_pengantar === \App\Enums\ModePengantar::VerifikasiOnline), persetujuan RT/RW di aplikasi @endif</p>
                    <a href="{{ route('warga.pengajuan.buat', $item) }}" class="tombol mt-4 gap-2">@include('komponen.ikon', ['nama' => 'pengajuan', 'kelas' => 'h-4 w-4']) Isi formulir</a>
                </div>
            </article>
        @endforeach
    </div>
@endsection
