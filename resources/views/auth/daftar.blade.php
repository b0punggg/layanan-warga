@extends('layouts.tamu')

@section('judul', 'Daftar')

@section('isi')
    <div class="mx-auto max-w-md">
        <h1 class="font-serif text-3xl text-hijau-dalam">Daftar akun warga</h1>
        <p class="mt-2 text-sm leading-relaxed text-tinta/70">Isi NIK, nama sesuai KTP, dan nomor HP. Setelah kode HP benar, unggah foto KTP dan buat kata sandi. Data lain terisi dari catatan kalurahan atau dari foto bila terbaca jelas.</p>
        <form method="POST" action="{{ route('daftar.mulai') }}" class="mt-6 space-y-4">
            @csrf
            @include('komponen.ringkasan-galat')
            <div>
                <label for="nik" class="mb-1 block text-sm font-semibold">NIK</label>
                <input id="nik" name="nik" inputmode="numeric" maxlength="16" value="{{ old('nik') }}" required @if($errors->has('nik')) aria-invalid="true" aria-describedby="galat-nik" @endif class="bidang{{ $errors->has('nik') ? ' bidang-salah' : '' }}">
                @include('komponen.galat', ['nama' => 'nik'])
            </div>
            <div>
                <label for="nama" class="mb-1 block text-sm font-semibold">Nama lengkap</label>
                <input id="nama" name="nama" value="{{ old('nama') }}" required @if($errors->has('nama')) aria-invalid="true" aria-describedby="galat-nama" @endif class="bidang{{ $errors->has('nama') ? ' bidang-salah' : '' }}">
                @include('komponen.galat', ['nama' => 'nama'])
            </div>
            <div>
                <label for="no_hp" class="mb-1 block text-sm font-semibold">Nomor HP (WhatsApp)</label>
                <input id="no_hp" name="no_hp" value="{{ old('no_hp') }}" placeholder="08xxxxxxxxxx" required @if($errors->has('no_hp')) aria-invalid="true" aria-describedby="galat-no_hp" @endif class="bidang{{ $errors->has('no_hp') ? ' bidang-salah' : '' }}">
                @include('komponen.galat', ['nama' => 'no_hp'])
                <p class="mt-1 text-sm text-tinta/70">Kode verifikasi dikirim ke nomor ini dari WhatsApp {{ config('surat.notifikasi.nomor_whatsapp') }}.</p>
            </div>
            <div>
                <label class="flex min-h-11 items-start gap-2 text-sm leading-relaxed">
                    <input type="checkbox" name="setuju_data" value="1" class="mt-1 h-4 w-4 accent-hijau" @checked(old('setuju_data'))>
                    Saya setuju data pribadi dipakai untuk memproses surat kalurahan dan disimpan sesuai kebutuhan layanan.
                </label>
                @include('komponen.galat', ['nama' => 'setuju_data'])
            </div>
            <button class="tombol w-full" type="submit">Lanjut</button>
        </form>
    </div>
@endsection
