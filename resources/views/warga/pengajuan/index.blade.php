@extends('layouts.aplikasi')

@section('judul', 'Pengajuan')

@section('isi')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h1 class="font-serif text-3xl text-hijau-dalam">Pengajuan saya</h1>
        @if ($bolehMengajukan)
            <a href="{{ route('warga.pengajuan.layanan') }}" class="tombol">Ajukan surat</a>
        @endif
    </div>
    @if ($pengajuan->isEmpty())
        <p class="kartu mt-4 text-sm text-tinta/70">Belum ada pengajuan.</p>
    @else
        <div class="mt-4 space-y-2">
            @foreach ($pengajuan as $item)
                <a href="{{ route('warga.pengajuan.lihat', $item) }}" class="kartu flex items-center justify-between gap-3 text-sm">
                    <span>
                        <span class="block font-semibold">{{ $item->jenisLayanan->nama }}</span>
                        <span class="text-tinta/70">{{ $item->kode_tiket }}</span>
                    </span>
                    <span>{{ $item->status->label() }}</span>
                </a>
            @endforeach
        </div>
    @endif
@endsection
