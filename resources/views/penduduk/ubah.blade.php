@extends('layouts.aplikasi')

@section('judul', 'Ubah penduduk')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Ubah data penduduk</h1>
    <form method="POST" action="{{ route('penduduk.simpan', $penduduk) }}" class="mt-6 max-w-lg space-y-4">
        @csrf
        @method('PUT')
        @if (auth()->user()->peran === \App\Enums\Peran::Admin)
            <div><label class="mb-1 block text-sm font-semibold">NIK</label><input name="nik" value="{{ old('nik', $penduduk->nik) }}" class="bidang"></div>
        @endif
        <div><label class="mb-1 block text-sm font-semibold">Nama</label><input name="nama" value="{{ old('nama', $penduduk->nama) }}" required class="bidang"></div>
        <div><label class="mb-1 block text-sm font-semibold">Alamat asli</label><textarea name="alamat_asli" required class="bidang min-h-24 py-2">{{ old('alamat_asli', $penduduk->alamat_asli) }}</textarea></div>
        <div><label class="mb-1 block text-sm font-semibold">Dukuh</label><input name="dukuh" value="{{ old('dukuh', $penduduk->dukuh) }}" class="bidang"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="mb-1 block text-sm font-semibold">RT</label><input name="rt" value="{{ old('rt', $penduduk->rt) }}" class="bidang"></div>
            <div><label class="mb-1 block text-sm font-semibold">RW</label><input name="rw" value="{{ old('rw', $penduduk->rw) }}" class="bidang"></div>
        </div>
        <button class="tombol" type="submit">Simpan</button>
    </form>
@endsection
