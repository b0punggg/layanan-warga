@extends('layouts.tamu')

@section('judul', 'Masuk')

@section('isi')
    <div class="mx-auto max-w-md">
        <img src="{{ asset('images/logo-sindumartani.png') }}" alt="" width="48" height="48" class="mb-4 h-12 w-12 rounded-full bg-white object-cover ring-1 ring-black/5">
        <h1 class="text-3xl font-extrabold tracking-tight text-hijau-dalam">Masuk</h1>
        @if (session('status'))
            <p class="mt-3 rounded-xl border border-daun/30 bg-white px-4 py-3 text-sm text-hijau">{{ session('status') }}</p>
        @endif
        <p class="mt-2 text-sm leading-relaxed text-tinta/70">Layanan surat Kalurahan Sindumartani. Pakai email staf, atau email maupun NIK untuk warga.</p>

        <form method="POST" action="{{ route('masuk.store') }}" class="mt-6 space-y-4">
            @csrf
            @include('komponen.ringkasan-galat')
            <div>
                <label for="login" class="mb-1 block text-sm font-semibold">Email atau NIK</label>
                <input id="login" name="login" type="text" value="{{ old('login') }}" autocomplete="username" required autofocus @if($errors->has('login')) aria-invalid="true" aria-describedby="galat-login" @endif class="bidang{{ $errors->has('login') ? ' bidang-salah' : '' }}">
                @include('komponen.galat', ['nama' => 'login'])
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-semibold">Kata sandi</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required @if($errors->has('password')) aria-invalid="true" aria-describedby="galat-password" @endif class="bidang{{ $errors->has('password') ? ' bidang-salah' : '' }}">
                @include('komponen.galat', ['nama' => 'password'])
            </div>
            <label class="flex min-h-11 items-center gap-2 text-sm">
                <input type="checkbox" name="ingat" value="1" class="h-4 w-4 accent-hijau">
                Ingat saya di perangkat ini
            </label>
            <button type="submit" class="tombol w-full">Masuk</button>
        </form>

        <p class="mt-4 text-sm text-tinta/70"><a href="{{ route('sandi.minta') }}" class="font-semibold text-hijau">Lupa kata sandi</a> jika akun punya email. Tautannya dikirim ke email dan WhatsApp akun itu. Selain itu, hubungi operator.</p>
        <p class="mt-2 text-sm text-tinta/70">Belum punya akun? <a href="{{ route('daftar') }}" class="font-semibold text-hijau">Daftar di sini</a>.</p>

        @if ($akunPercobaan !== [])
            <div class="kartu mt-8">
                <p class="text-sm font-semibold">Akun percobaan di komputer ini</p>
                <p class="mt-1 text-sm text-tinta/70">Kata sandi: {{ $kataSandiPercobaan }}</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($akunPercobaan as $akun)
                        <li class="flex justify-between gap-3 border-b border-garis pb-2">
                            <span>{{ $akun['peran'] }}</span>
                            <span class="text-right text-tinta/70">{{ $akun['email'] }}@if ($akun['nik'] !== '—')<br>NIK {{ $akun['nik'] }}@endif</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
