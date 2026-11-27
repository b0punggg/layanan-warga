@extends('layouts.aplikasi')

@section('judul', ($jenis->naskahResmi() ? $jenis->nama : 'Draf '.$jenis->nama))

@section('isi')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            @if ($jenis->naskahResmi())
                <p class="text-sm font-semibold text-hijau-dalam">Naskah resmi Kalurahan Sindumartani, kertas F4</p>
            @else
                <p class="text-sm font-semibold text-tanah">Draf, belum dikonfirmasi kalurahan</p>
            @endif
            <h1 class="font-serif text-3xl text-hijau-dalam">{{ $jenis->nama }}</h1>
        </div>
        <a href="{{ route('admin.layanan.index') }}" class="tombol-tenang">Kembali</a>
    </div>
    @if ($jenis->kode === 'SKU')
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-tinta/70">Nomor contoh {{ $nomor }} memakai pola pada berkas resmi: 400.10.5 diikuti nomor urut. Angka 151 pada contoh kertas hanya urutan saat itu, bukan nomor tetap.</p>
    @elseif ($jenis->naskahResmi())
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-tinta/70">Nomor contoh {{ $nomor }} memakai pola pada berkas resmi. Angka pada contoh kertas hanya urutan saat itu, bukan nomor tetap.</p>
    @else
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-tinta/70">Kop dan ukuran kertas F4 sudah mengikuti Kalurahan Sindumartani. Isi naskah ini masih draf. Nomor contoh {{ $nomor }} memakai format yang belum dikunci.</p>
    @endif

    @if ($jenis->pernyataan)
        <section class="kartu mt-6 max-w-3xl text-sm leading-relaxed">
            <p>Surat pernyataan ini tanpa kop dan nomor. Isi ada pada berkas Word yang diunggah.</p>
        </section>
    @else
    <article class="lembar mx-auto mt-6 bg-white shadow-md" style="width: min(100%, 215.9mm); min-height: 330.2mm; padding: 22.5mm 16mm 22.5mm 25mm;">
        @include($jenis->tampilanSurat())
    </article>
    @endif
@endsection
