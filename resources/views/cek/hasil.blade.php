@extends('layouts.tamu')

@section('judul', 'Hasil pemeriksaan')

@section('isi')
    <div class="mx-auto max-w-lg">
        @if ($sah)
            <p class="text-sm font-semibold text-hijau">Surat tercatat</p>
            <h1 class="mt-2 font-serif text-3xl text-hijau-dalam">{{ $surat->pengajuan->jenisLayanan->nama }}</h1>
            <dl class="kartu mt-4 space-y-2 text-sm">
                <div><dt class="font-semibold">Nomor</dt><dd>{{ $surat->nomor_surat }}</dd></div>
                <div><dt class="font-semibold">Nama</dt><dd>{{ $surat->pengajuan->pemohon->name }}</dd></div>
                <div><dt class="font-semibold">Tanggal terbit</dt><dd>{{ $surat->tanggal_terbit?->translatedFormat('d F Y') }}</dd></div>
            </dl>
            <p class="mt-4 text-sm leading-relaxed text-tinta/70">Data ini sesuai arsip kalurahan. NIK dan berkas tidak ditampilkan.</p>
        @else
            <h1 class="font-serif text-3xl text-hijau-dalam">Surat tidak ditemukan</h1>
            <p class="mt-2 text-sm leading-relaxed text-tinta/70">Kode ini tidak cocok dengan surat yang sudah terbit. Periksa kembali kode pada lembar surat.</p>
        @endif
        <a href="{{ route('cek.form') }}" class="tombol-tenang mt-6">Periksa kode lain</a>
    </div>
@endsection
