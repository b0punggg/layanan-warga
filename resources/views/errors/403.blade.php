@extends('layouts.tamu')

@section('judul', 'Akses ditolak')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Halaman ini di luar peran Anda</h1>
    <p class="mt-3 max-w-lg text-sm leading-relaxed text-tinta/70">{{ $exception->getMessage() ?: 'Akun yang sedang masuk tidak boleh membuka halaman ini.' }}</p>
    <a href="{{ route('beranda') }}" class="tombol mt-6">Kembali ke beranda</a>
@endsection
