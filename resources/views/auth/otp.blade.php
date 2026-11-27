@extends('layouts.tamu')

@section('judul', 'Kode OTP')

@section('isi')
    <div class="mx-auto max-w-md">
        <h1 class="font-serif text-3xl text-hijau-dalam">Masukkan kode</h1>
        @if (session('status'))
            <p class="mt-3 rounded-xl border border-daun/30 bg-white px-4 py-3 text-sm">{{ session('status') }}</p>
        @endif
        @if ($kodePengembangan)
            <p class="mt-3 rounded-xl border border-tanah/40 bg-white px-4 py-3 text-sm">Kode pengembangan di komputer ini: <strong>{{ $kodePengembangan }}</strong>. Di server sungguhan, kode ini dikirim dari WhatsApp {{ config('surat.notifikasi.nomor_whatsapp') }}.</p>
        @endif
        <form method="POST" action="{{ route('daftar.otp.periksa') }}" class="mt-6 space-y-4">
            @csrf
            @include('komponen.ringkasan-galat')
            <div>
                <label for="kode" class="mb-1 block text-sm font-semibold">Kode 6 digit</label>
                <input id="kode" name="kode" inputmode="numeric" maxlength="6" required @if($errors->has('kode')) aria-invalid="true" aria-describedby="galat-kode" @endif class="bidang{{ $errors->has('kode') ? ' bidang-salah' : '' }}">
                @include('komponen.galat', ['nama' => 'kode'])
            </div>
            <button class="tombol w-full" type="submit">Periksa kode</button>
        </form>
    </div>
@endsection
