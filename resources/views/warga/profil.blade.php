@extends('layouts.aplikasi')

@section('judul', 'Profil')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Profil</h1>
    @if ($menunggu)
        <p class="mt-2 text-sm text-tinta/70">
            @if ($profil?->status_verifikasi?->value === 'perbaikan')
                Operator meminta perbaikan data. Setelah disimpan, pemeriksaan dilanjutkan. Kabar hasil dikirim ke WhatsApp Anda.
            @else
                Akun menunggu pemeriksaan operator. Kabar persetujuan dikirim ke WhatsApp Anda.
            @endif
            @if ($profil?->catatan) Catatan: {{ $profil->catatan }} @endif
        </p>
    @endif
    @if ($profil)
        <p class="mt-2 text-sm">{{ auth()->user()->name }} · NIK {{ auth()->user()->nik }} · {{ $profil->status_verifikasi->label() }}</p>
        <form method="POST" action="{{ route('warga.profil.simpan') }}" enctype="multipart/form-data" class="mt-6 max-w-lg space-y-4">
            @csrf
            @method('PUT')
            @include('komponen.ringkasan-galat')
            @foreach ([
                'no_hp' => ['Nomor HP', old('no_hp', $profil->no_hp), true],
                'email' => ['Email', old('email', $profil->email), false],
                'pekerjaan' => ['Pekerjaan', old('pekerjaan', $profil->pekerjaan), false],
                'dukuh' => ['Dukuh', old('dukuh', $profil->dukuh), true],
            ] as $nama => [$label, $nilai, $wajib])
                <div>
                    <label class="mb-1 block text-sm font-semibold" for="{{ $nama }}">{{ $label }}</label>
                    <input id="{{ $nama }}" name="{{ $nama }}" @if($nama === 'email') type="email" @endif value="{{ $nilai }}" @required($wajib) @if($errors->has($nama)) aria-invalid="true" aria-describedby="galat-{{ $nama }}" @endif class="bidang{{ $errors->has($nama) ? ' bidang-salah' : '' }}">
                    @include('komponen.galat', ['nama' => $nama])
                </div>
            @endforeach
            <div class="grid grid-cols-2 gap-3">
                @foreach (['rt' => 'RT', 'rw' => 'RW'] as $nama => $label)
                    <div>
                        <label class="mb-1 block text-sm font-semibold" for="{{ $nama }}">{{ $label }}</label>
                        <input id="{{ $nama }}" name="{{ $nama }}" value="{{ old($nama, $profil->{$nama}) }}" required @if($errors->has($nama)) aria-invalid="true" aria-describedby="galat-{{ $nama }}" @endif class="bidang{{ $errors->has($nama) ? ' bidang-salah' : '' }}">
                        @include('komponen.galat', ['nama' => $nama])
                    </div>
                @endforeach
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold" for="alamat_lengkap">Alamat</label>
                <textarea id="alamat_lengkap" name="alamat_lengkap" required @if($errors->has('alamat_lengkap')) aria-invalid="true" aria-describedby="galat-alamat_lengkap" @endif class="bidang min-h-24 py-2{{ $errors->has('alamat_lengkap') ? ' bidang-salah' : '' }}">{{ old('alamat_lengkap', $profil->alamat_lengkap) }}</textarea>
                @include('komponen.galat', ['nama' => 'alamat_lengkap'])
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold" for="foto_ktp">Ganti foto KTP</label>
                <input id="foto_ktp" name="foto_ktp" type="file" accept="image/jpeg,image/png" @if($errors->has('foto_ktp')) aria-invalid="true" aria-describedby="galat-foto_ktp" @endif class="bidang py-2{{ $errors->has('foto_ktp') ? ' bidang-salah' : '' }}">
                @include('komponen.galat', ['nama' => 'foto_ktp'])
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold" for="foto_selfie">Swafoto, hanya jika operator meminta</label>
                <input id="foto_selfie" name="foto_selfie" type="file" accept="image/jpeg,image/png" @if($errors->has('foto_selfie')) aria-invalid="true" aria-describedby="galat-foto_selfie" @endif class="bidang py-2{{ $errors->has('foto_selfie') ? ' bidang-salah' : '' }}">
                @include('komponen.galat', ['nama' => 'foto_selfie'])
            </div>
            <button class="tombol" type="submit">Simpan profil</button>
        </form>
    @else
        <p class="mt-4 text-sm text-tinta/70">Profil belum ada.</p>
    @endif
@endsection
