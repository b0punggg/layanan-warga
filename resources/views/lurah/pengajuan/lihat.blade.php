@extends('layouts.aplikasi')

@section('judul', 'Tinjau surat')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">{{ $pengajuan->jenisLayanan->nama }}</h1>
    <p class="mt-1 text-sm text-tinta/70">{{ $pengajuan->pemohon->name }} · {{ $pengajuan->surat->nomor_surat ?? 'Belum bernomor' }}</p>
    <article class="lembar mx-auto mt-6 bg-white shadow-md" style="width: min(100%, 215.9mm); min-height: 330.2mm; padding: 22.5mm 16mm 22.5mm 25mm;">
        @include($pengajuan->jenisLayanan->tampilanSurat(), [
            'nomor' => $pengajuan->surat->nomor_surat ?? '………………',
            'contoh' => $contoh,
            'badan' => $badan,
        ])
    </article>
    <div class="mt-6 flex flex-wrap gap-3">
        <form method="POST" action="{{ route('lurah.pengajuan.setujui', $pengajuan) }}">
            @csrf
            <button class="tombol" type="submit">Setujui</button>
        </form>
    </div>
    <form method="POST" action="{{ route('lurah.pengajuan.tolak', $pengajuan) }}" class="mt-4 max-w-lg space-y-3">
        @csrf
        <textarea name="catatan" required class="bidang min-h-24 py-2" placeholder="Alasan penolakan"></textarea>
        <button class="tombol-tenang" type="submit">Tolak</button>
    </form>
@endsection
