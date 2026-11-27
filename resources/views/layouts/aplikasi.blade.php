<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Beranda') — Kalurahan Sindumartani</title>
    <link rel="icon" href="{{ asset('images/logo-sindumartani.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {!! \Illuminate\Support\Facades\Vite::fonts() !!}
</head>
<body class="min-h-screen bg-kertas font-sans text-tinta antialiased">
    @php
        $user = auth()->user();
        $tautan = match ($user->peran) {
            \App\Enums\Peran::Admin => [
                ['admin.beranda', 'Beranda', 'beranda'],
                ['admin.impor.buat', 'Impor', 'impor'],
                ['laporan.index', 'Laporan', 'laporan'],
                ['penduduk.index', 'Penduduk', 'penduduk'],
                ['arsip.index', 'Arsip', 'arsip'],
                ['admin.pengguna.index', 'Pengguna', 'pengguna'],
                ['admin.layanan.index', 'Jenis layanan', 'layanan'],
                ['admin.informasi.index', 'Informasi', 'informasi'],
                ['pengaduan.index', 'Pengaduan', 'pengaduan'],
                ['admin.audit.index', 'Audit', 'audit'],
                ['admin.persiapan', 'Persiapan', 'persiapan'],
            ],
            \App\Enums\Peran::Operator => [
                ['operator.beranda', 'Beranda', 'beranda'],
                ['operator.pengajuan.index', 'Antrean', 'antrean'],
                ['operator.templat.index', 'Template surat', 'layanan'],
                ['operator.akun.index', 'Verifikasi akun', 'verifikasi'],
                ['pengaduan.index', 'Pengaduan', 'pengaduan'],
                ['penduduk.index', 'Penduduk', 'penduduk'],
                ['arsip.index', 'Arsip', 'arsip'],
                ['laporan.index', 'Laporan', 'laporan'],
            ],
            \App\Enums\Peran::Lurah => [
                ['lurah.beranda', 'Beranda', 'beranda'],
                ['lurah.pengajuan.index', 'Persetujuan', 'verifikasi'],
                ['arsip.index', 'Arsip', 'arsip'],
                ['laporan.index', 'Laporan', 'laporan'],
            ],
            \App\Enums\Peran::Warga => $user->status_akun === \App\Enums\StatusAkun::Menunggu
                ? [['warga.profil', 'Profil', 'profil']]
                : [
                    ['warga.beranda', 'Beranda', 'beranda'],
                    ['warga.pengajuan.index', 'Pengajuan', 'pengajuan'],
                    ['warga.pesan', 'Pesan', 'pesan'],
                    ['warga.pengaduan', 'Aduan', 'pengaduan'],
                    ['warga.profil', 'Profil', 'profil'],
                ],
            \App\Enums\Peran::RtRw => [
                ['rt.beranda', 'Pengantar', 'pengantar'],
            ],
            default => [
                [$user->berandaRoute(), 'Beranda', 'beranda'],
            ],
        };
    @endphp

    <header class="sticky top-0 z-20 border-b border-garis bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3">
            @include('layouts._merek', ['tautan' => route('beranda')])
            <div class="text-right text-sm">
                <p class="font-semibold text-hijau-dalam">{{ $user->name }}</p>
                <p class="text-xs text-tinta/55">{{ $user->peran->label() }}</p>
            </div>
        </div>
        <nav class="mx-auto flex max-w-5xl items-center gap-2 overflow-x-auto px-4 pb-3" aria-label="Menu utama">
            @foreach ($tautan as [$rute, $label, $ikon])
                <a
                    href="{{ route($rute) }}"
                    @class([
                        'inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full px-4 text-sm font-semibold',
                        'bg-hijau text-white' => request()->routeIs($rute),
                        'text-tinta hover:bg-kertas' => ! request()->routeIs($rute),
                    ])
                >@include('komponen.ikon', ['nama' => $ikon, 'kelas' => 'h-4 w-4']) {{ $label }}</a>
            @endforeach
            <form method="POST" action="{{ route('keluar') }}" class="ml-auto">
                @csrf
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-full px-4 text-sm font-semibold text-tanah">@include('komponen.ikon', ['nama' => 'keluar', 'kelas' => 'h-4 w-4']) Keluar</button>
            </form>
        </nav>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        @if (session('status'))
            <p class="mb-4 rounded-xl border border-daun/30 bg-white px-4 py-3 text-sm text-hijau">{{ session('status') }}</p>
        @endif
        @yield('isi')
    </main>
</body>
</html>
