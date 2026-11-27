@extends('layouts.aplikasi')

@section('judul', 'Daftar di loket')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Daftarkan warga di loket</h1>
    <p class="mt-2 text-sm text-tinta/70">Akun langsung aktif karena warga hadir di kantor. Sampaikan kata sandi kepada warga.</p>
    <form method="POST" action="{{ route('operator.akun.simpan') }}" enctype="multipart/form-data" class="mt-6 max-w-lg space-y-4">
        @csrf
        <div><label class="mb-1 block text-sm font-semibold">NIK</label><input name="nik" value="{{ old('nik') }}" required class="bidang"></div>
        <div><label class="mb-1 block text-sm font-semibold">Nama</label><input name="nama" value="{{ old('nama') }}" required class="bidang"></div>
        <div><label class="mb-1 block text-sm font-semibold">Nomor HP</label><input name="no_hp" value="{{ old('no_hp') }}" required class="bidang"></div>
        <div><label class="mb-1 block text-sm font-semibold">Alamat</label><textarea name="alamat_lengkap" class="bidang min-h-24 py-2">{{ old('alamat_lengkap') }}</textarea></div>
        <div><label class="mb-1 block text-sm font-semibold">Kata sandi sementara</label><input name="password" type="password" required class="bidang"></div>
        <div><label class="mb-1 block text-sm font-semibold">Foto KTP</label><input name="foto_ktp" type="file" accept="image/jpeg,image/png" class="bidang py-2"></div>
        @if ($errors->any())
            <div class="rounded-xl border border-tanah/40 bg-white px-4 py-3 text-sm text-tanah">@foreach ($errors->all() as $pesan)<p>{{ $pesan }}</p>@endforeach</div>
        @endif
        <button class="tombol" type="submit">Simpan</button>
    </form>
@endsection
