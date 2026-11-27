@extends('layouts.tamu')

@section('judul', 'Lupa kata sandi')

@section('isi')
    <div class="mx-auto max-w-md">
        <h1 class="font-serif text-3xl text-hijau-dalam">Atur ulang kata sandi</h1>
        <p class="mt-2 text-sm leading-relaxed text-tinta/70">Tautan dikirim ke email ini dan ke WhatsApp nomor yang tersimpan pada akun.</p>
        @if (session('status'))
            <p class="mt-3 rounded-xl border border-daun/30 bg-white px-4 py-3 text-sm">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('sandi.kirim') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="mb-1 block text-sm font-semibold">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="bidang">
            </div>
            <button class="tombol w-full" type="submit">Kirim tautan</button>
        </form>
    </div>
@endsection
