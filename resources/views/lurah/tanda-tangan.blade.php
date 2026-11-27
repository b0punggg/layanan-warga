@extends('layouts.aplikasi')

@section('judul', 'Tanda tangan')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Gambar tanda tangan</h1>
    <p class="mt-2 text-sm text-tinta/70">Gambar ini ditempel pada PDF. Ini belum tanda tangan elektronik tersertifikasi.</p>
    @if ($pejabat?->file_ttd)
        <p class="mt-3 text-sm">Gambar saat ini sudah tersimpan untuk {{ $pejabat->nama }}.</p>
    @endif
    <form method="POST" action="{{ route('lurah.ttd.simpan') }}" enctype="multipart/form-data" class="mt-6 max-w-lg space-y-4">
        @csrf
        <input type="file" name="file_ttd" accept="image/jpeg,image/png" required class="bidang py-2">
        @if ($errors->any())
            <div class="text-sm text-tanah">@foreach ($errors->all() as $pesan)<p>{{ $pesan }}</p>@endforeach</div>
        @endif
        <button class="tombol" type="submit">Simpan</button>
    </form>
@endsection
