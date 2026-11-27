@extends('layouts.aplikasi')

@section('judul', 'Periksa akun')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">{{ $profil->user->name }}</h1>
    <p class="mt-1 text-sm text-tinta/70">NIK {{ $profil->user->nik }} · HP {{ $profil->no_hp }}</p>
    <dl class="kartu mt-4 grid gap-2 text-sm sm:grid-cols-2">
        <div><dt class="font-semibold">Nama di data impor</dt><dd>{{ $profil->penduduk->nama }}</dd></div>
        <div><dt class="font-semibold">Alamat impor</dt><dd>{{ $profil->penduduk->alamat_asli }}</dd></div>
        <div><dt class="font-semibold">Alamat di profil</dt><dd>{{ $profil->alamat_lengkap }}</dd></div>
        <div><dt class="font-semibold">Pekerjaan</dt><dd>{{ $profil->pekerjaan }}</dd></div>
    </dl>
    <div class="mt-4 flex flex-wrap gap-4 text-sm">
        @if ($profil->foto_ktp_path)
            <a class="font-semibold text-hijau" href="{{ route('berkas.identitas', [$profil, 'ktp']) }}">Lihat KTP</a>
        @endif
        @if ($profil->foto_selfie_path)
            <a class="font-semibold text-hijau" href="{{ route('berkas.identitas', [$profil, 'selfie']) }}">Lihat swafoto</a>
        @endif
    </div>
    <form method="POST" action="{{ route('operator.akun.setujui', $profil) }}" class="mt-6">
        @csrf
        <button class="tombol" type="submit">Setujui akun</button>
    </form>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <form method="POST" action="{{ route('operator.akun.perbaikan', $profil) }}" class="kartu space-y-3">
            @csrf
            <textarea name="catatan" required class="bidang min-h-24 py-2" placeholder="Yang perlu diperbaiki"></textarea>
            <button class="tombol-tenang" type="submit">Minta perbaikan</button>
        </form>
        <form method="POST" action="{{ route('operator.akun.tolak', $profil) }}" class="kartu space-y-3">
            @csrf
            <textarea name="catatan" required class="bidang min-h-24 py-2" placeholder="Alasan penolakan"></textarea>
            <button class="tombol" type="submit">Tolak</button>
        </form>
    </div>
@endsection
