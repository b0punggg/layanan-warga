@extends('layouts.aplikasi')

@section('judul', 'Verifikasi akun')

@section('isi')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h1 class="font-serif text-3xl text-hijau-dalam">Verifikasi akun warga</h1>
        <a href="{{ route('operator.akun.buat') }}" class="tombol">Daftarkan di loket</a>
    </div>
    <div class="mt-4 space-y-2">
        @forelse ($antrean as $profil)
            <a href="{{ route('operator.akun.lihat', $profil) }}" class="kartu flex justify-between text-sm">
                <span>
                    <span class="block font-semibold">{{ $profil->user->name }}</span>
                    <span class="text-tinta/70">NIK {{ $profil->user->nik }}</span>
                </span>
                <span>{{ $profil->status_verifikasi->label() }}</span>
            </a>
        @empty
            <p class="kartu text-sm text-tinta/70">Tidak ada akun yang menunggu.</p>
        @endforelse
    </div>
@endsection
