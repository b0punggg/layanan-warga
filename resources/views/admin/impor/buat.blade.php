@extends('layouts.aplikasi')

@section('judul', 'Impor penduduk')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Impor penduduk</h1>
    <p class="mt-2 max-w-2xl text-sm text-tinta/70">Berkas CSV atau Excel dengan kolom nama, nik, dan alamat. Data yang NIK-nya sudah ada akan diperbarui, bukan menghapus seluruh tabel.</p>
    <form method="POST" action="{{ route('admin.impor.pratinjau') }}" enctype="multipart/form-data" class="mt-6 max-w-lg space-y-4">
        @csrf
        <input type="file" name="berkas" accept=".csv,.txt,.xlsx" required class="bidang py-2">
        @if ($errors->any())
            <div class="text-sm text-tanah">@foreach ($errors->all() as $pesan)<p>{{ $pesan }}</p>@endforeach</div>
        @endif
        <button class="tombol" type="submit">Periksa berkas</button>
    </form>
    <h2 class="mt-8 font-serif text-2xl">Riwayat impor</h2>
    <ul class="mt-3 space-y-2 text-sm">
        @forelse ($riwayat as $item)
            <li class="kartu">
                {{ $item->nama_file }} · {{ $item->baris_sukses }} sukses · {{ $item->baris_gagal }} gagal · {{ $item->pelaku->name ?? 'Sistem' }}
                @if ($item->laporan_gagal_path)
                    <a class="font-semibold text-hijau" href="{{ route('admin.impor.laporan', $item) }}">Unduh gagal</a>
                @endif
            </li>
        @empty
            <li class="text-tinta/70">Belum ada impor.</li>
        @endforelse
    </ul>
@endsection
