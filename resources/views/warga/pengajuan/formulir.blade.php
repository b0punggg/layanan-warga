@extends('layouts.aplikasi')

@section('judul', $jenis->nama)

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">{{ $jenis->nama }}</h1>
    <p class="mt-2 text-sm text-tinta/70">Isian ini sama dengan baris pada surat. Data diri sudah diisi dari profil; lengkapi yang masih kosong sebelum dikirim.</p>
    @include('warga.pengajuan._formulir')
@endsection
