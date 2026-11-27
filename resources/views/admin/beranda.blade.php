@extends('layouts.aplikasi')

@section('judul', 'Beranda admin')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Ruang admin</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-tinta/70">Fondasi akun, jenis surat, dan jejak aktivitas. Impor penduduk menyusul pada tahap berikutnya.</p>

    @unless (config('surat.format_dikonfirmasi'))
        <p class="mt-4 rounded-xl border border-tanah/40 bg-white px-4 py-3 text-sm leading-relaxed text-tanah">Format nomor surat masih contoh: {{ $contohNomor }}. Kunci bersama kalurahan lewat halaman Persiapan sebelum surat diterbitkan.</p>
    @endunless

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach (\App\Enums\Peran::cases() as $peran)
            <article class="kartu">
                <p class="text-sm text-tinta/70">{{ $peran->label() }}</p>
                <p class="mt-2 font-serif text-4xl text-hijau">{{ $jumlahPeran[$peran->value] ?? 0 }}</p>
            </article>
        @endforeach
        <article class="kartu">
            <p class="text-sm text-tinta/70">Jenis layanan aktif</p>
            <p class="mt-2 font-serif text-4xl text-hijau">{{ $jumlahLayanan }}</p>
        </article>
    </div>

    <section class="mt-8">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-serif text-2xl">Aktivitas terakhir</h2>
            <a href="{{ route('admin.audit.index') }}" class="text-sm font-semibold text-hijau">Lihat semua</a>
        </div>
        @if ($audit->isEmpty())
            <p class="kartu mt-3 text-sm text-tinta/70">Belum ada aktivitas tercatat.</p>
        @else
            <ul class="mt-3 space-y-2">
                @foreach ($audit as $jejak)
                    <li class="kartu flex flex-wrap items-baseline justify-between gap-2 text-sm">
                        <span class="font-semibold">{{ $jejak->aksi }}</span>
                        <span class="text-tinta/70">{{ $jejak->user?->name ?? 'Sistem' }} · {{ $jejak->waktu->translatedFormat('d MMM yyyy HH:mm') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
