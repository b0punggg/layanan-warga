@extends('layouts.aplikasi')

@section('judul', 'Beranda Lurah')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Meja Lurah</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-tinta/70">Surat yang sudah diperiksa operator menunggu persetujuan di sini. Tanda tangan elektronik dilakukan di SIMARDA, bukan dengan mengunggah gambar.</p>

    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        <article class="kartu">
            <p class="text-sm text-tinta/70">Menunggu persetujuan</p>
            <p class="mt-2 font-serif text-4xl text-hijau">{{ $menunggu }}</p>
        </article>
        <article class="kartu">
            <p class="text-sm text-tinta/70">Surat selesai</p>
            <p class="mt-2 font-serif text-4xl text-hijau">{{ $selesai }}</p>
        </article>
    </div>

    <section class="mt-8">
        <h2 class="font-serif text-2xl">Pejabat penandatangan</h2>
        @if ($pejabat->isEmpty())
            <p class="kartu mt-3 text-sm text-tinta/70">Belum ada pejabat aktif.</p>
        @else
            <ul class="mt-3 space-y-2">
                @foreach ($pejabat as $orang)
                    <li class="kartu text-sm">
                        <p class="font-semibold">{{ $orang->nama }}</p>
                        <p class="text-tinta/70">{{ $orang->jabatan }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
        <p class="mt-3 text-sm text-tinta/70">Nama Lurah tercetak pada naskah. Segel BSrE ditambahkan di SIMARDA.</p>
    </section>
@endsection
