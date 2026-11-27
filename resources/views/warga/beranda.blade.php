@extends('layouts.aplikasi')

@section('judul', 'Beranda warga')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Halo, {{ auth()->user()->name }}</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-tinta/70">Data diri yang sudah disetujui terisi otomatis pada setiap pengajuan.</p>
    @if (auth()->user()->bolehMengajukan())
        <a href="{{ route('warga.pengajuan.layanan') }}" class="tombol mt-4 gap-2">@include('komponen.ikon', ['nama' => 'pengajuan', 'kelas' => 'h-4 w-4']) Ajukan surat</a>
    @endif

    <ol class="mt-6 grid gap-3 sm:grid-cols-4">
        @foreach (['layanan' => 'Pilih jenis surat', 'berkas' => 'Lengkapi data dan berkas', 'cek' => 'Pantau status', 'unduh' => 'Unduh PDF'] as $ikon => $langkah)
            <li class="kartu text-sm">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-pasir text-hijau">@include('komponen.ikon', ['nama' => $ikon === 'berkas' ? 'arsip' : $ikon, 'kelas' => 'h-4 w-4'])</span>
                <p class="mt-3">{{ $langkah }}</p>
            </li>
        @endforeach
    </ol>

    <section class="mt-8">
        <h2 class="font-serif text-2xl">Pengajuan saya</h2>
        @if ($pengajuan->isEmpty())
            <p class="kartu mt-3 text-sm text-tinta/70">Belum ada pengajuan. Daftar ini akan terisi setelah layanan surat dibuka.</p>
        @else
            <ul class="mt-3 space-y-2">
                @foreach ($pengajuan as $item)
                    <li class="kartu flex items-center justify-between text-sm">
                        <span>{{ $item->kode_tiket }}</span>
                        <span>{{ $item->status->label() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
