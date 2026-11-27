@extends('layouts.aplikasi')

@section('judul', $pengajuan->kode_tiket)

@section('isi')
    <p class="text-sm font-semibold text-tanah">{{ $pengajuan->kode_tiket }}</p>
    <h1 class="font-serif text-3xl text-hijau-dalam">{{ $pengajuan->jenisLayanan->nama }}</h1>
    <p class="mt-2 text-sm">Status: {{ $pengajuan->status->label() }}</p>
    @if ($pengajuan->status_pengantar_rt && $pengajuan->status_pengantar_rt !== \App\Enums\StatusPengantarRt::TidakPerlu)
        <p class="mt-1 text-sm">Pengantar RT/RW: {{ $pengajuan->status_pengantar_rt->label() }}</p>
    @endif
    @if ($pengajuan->file_hasil)
        <a href="{{ route('berkas.format', $pengajuan) }}" class="tombol mt-4">Unduh format Word</a>
    @endif
    @if ($pengajuan->surat)
        <p class="mt-2 text-sm">Nomor surat: {{ $pengajuan->surat->nomor_surat }}</p>
        @if ($pengajuan->surat->file_pdf)
            <a href="{{ route('berkas.surat', $pengajuan->surat) }}" class="tombol mt-4">Unduh PDF</a>
        @elseif ($pengajuan->status === \App\Enums\StatusPengajuan::Disetujui)
            <p class="mt-3 text-sm text-tinta/70">Lurah sudah menyetujui. Surat dikirim ke Anda setelah ditandatangani.</p>
        @endif
    @endif
    @php $catatan = $pengajuan->riwayatStatus->sortByDesc('waktu')->firstWhere('catatan', '!=', null); @endphp
    @if ($catatan)
        <p class="kartu mt-4 text-sm">Catatan petugas: {{ $catatan->catatan }}</p>
    @endif
    <ul class="mt-4 space-y-2 text-sm">
        @foreach ($pengajuan->berkas as $berkas)
            <li><a class="font-semibold text-hijau" href="{{ route('berkas.syarat', $berkas) }}">{{ $berkas->nama_syarat }}</a></li>
        @endforeach
        @if ($pengajuan->berkas_pengantar_path)
            <li><a class="font-semibold text-hijau" href="{{ route('berkas.pengantar', $pengajuan) }}">Pengantar RT/RW</a></li>
        @endif
    </ul>
    @if (in_array($pengajuan->status, [\App\Enums\StatusPengajuan::Draft, \App\Enums\StatusPengajuan::PerluPerbaikan], true))
        <div class="mt-6">
            @include('warga.pengajuan._formulir', ['jenis' => $pengajuan->jenisLayanan, 'pengajuan' => $pengajuan])
        </div>
    @endif
@endsection
