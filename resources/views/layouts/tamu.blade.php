<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Kalurahan Sindumartani')</title>
    <link rel="icon" href="{{ asset('images/logo-sindumartani.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {!! \Illuminate\Support\Facades\Vite::fonts() !!}
</head>
<body class="min-h-screen bg-kertas font-sans text-tinta antialiased">
    <header class="border-b border-garis bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3">
            @include('layouts._merek')
            <nav class="flex flex-wrap items-center justify-end gap-x-4 gap-y-2 text-sm" aria-label="Menu umum">
                <a href="{{ route('informasi.index') }}" class="inline-flex items-center gap-1.5 font-semibold text-hijau">@include('komponen.ikon', ['nama' => 'informasi', 'kelas' => 'h-4 w-4']) Informasi</a>
                <a href="{{ route('pengaduan.buat') }}" class="inline-flex items-center gap-1.5 font-semibold text-hijau">@include('komponen.ikon', ['nama' => 'pengaduan', 'kelas' => 'h-4 w-4']) Pengaduan</a>
                <a href="{{ route('cek.form') }}" class="inline-flex items-center gap-1.5 font-semibold text-hijau">@include('komponen.ikon', ['nama' => 'cek', 'kelas' => 'h-4 w-4']) Cek surat</a>
                @auth
                    <a href="{{ route('beranda') }}" class="tombol gap-2">@include('komponen.ikon', ['nama' => 'beranda', 'kelas' => 'h-4 w-4']) Ke beranda</a>
                @else
                    <a href="{{ route('masuk') }}" class="tombol gap-2">@include('komponen.ikon', ['nama' => 'masuk', 'kelas' => 'h-4 w-4']) Masuk</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        @yield('isi')
    </main>
</body>
</html>
