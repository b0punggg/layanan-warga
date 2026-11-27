@extends('layouts.tamu')

@section('judul', 'Cek surat')

@section('isi')
    <div class="mx-auto max-w-lg">
        <h1 class="font-serif text-3xl text-hijau-dalam">Periksa keaslian surat</h1>
        <p class="mt-2 text-sm leading-relaxed text-tinta/70">Masukkan kode di bawah QR pada surat, atau pindai QR tersebut.</p>
        <form method="GET" action="{{ route('cek.form') }}" class="mt-6 space-y-4">
            <div>
                <label for="kode" class="mb-1 block text-sm font-semibold">Kode verifikasi</label>
                <input id="kode" name="kode" value="{{ request('kode') }}" required class="bidang">
            </div>
            <button class="tombol" type="submit">Periksa</button>
        </form>
    </div>
@endsection
