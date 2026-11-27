@extends('layouts.aplikasi')

@section('judul', 'Persiapan')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Yang perlu dikunci kalurahan</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-tinta/70">Pengembangan tahap pertama bisa berjalan dengan contoh di bawah. Penerbitan surat resmi menunggu jawaban ini.</p>

    <ol class="mt-6 space-y-3">
        @foreach (config('surat.pertanyaan_terbuka') as $pertanyaan)
            <li class="kartu text-sm leading-relaxed">
                <span class="font-serif text-lg text-hijau">{{ $loop->iteration }}</span>
                <p class="mt-1">{{ $pertanyaan }}</p>
            </li>
        @endforeach
    </ol>

    <section class="kartu mt-6 text-sm leading-relaxed">
        <p class="font-semibold">Tanda tangan</p>
        <p class="mt-1">Lurah menyetujui di aplikasi ini tanpa mengunggah gambar tanda tangan. PDF naskah ditandatangani di SIMARDA memakai BSrE, lalu operator mengunggah PDF bersegel itu untuk dikirim ke warga.</p>
    </section>

    <section class="kartu mt-6 text-sm leading-relaxed">
        <p class="font-semibold">Format nomor yang dipakai sekarang</p>
        <p class="mt-1 font-mono">{{ config('surat.format') }}</p>
        <p class="mt-2 text-tinta/70">{{ config('surat.format_dikonfirmasi') ? 'Sudah dikonfirmasi.' : 'Belum dikonfirmasi. Ubah SURAT_NOMOR_DIKONFIRMASI setelah kalurahan menyetujui.' }}</p>
    </section>
@endsection
