@extends('layouts.aplikasi')

@section('judul', $informasi->exists ? 'Ubah informasi' : 'Informasi baru')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">{{ $informasi->exists ? 'Ubah informasi' : 'Informasi baru' }}</h1>
    <form method="POST" action="{{ $informasi->exists ? route('admin.informasi.perbarui', $informasi) : route('admin.informasi.simpan') }}" class="mt-6 max-w-2xl space-y-4">
        @csrf
        @if ($informasi->exists) @method('PUT') @endif
        <div>
            <label class="mb-1 block text-sm font-semibold" for="judul">Judul</label>
            <input id="judul" name="judul" value="{{ old('judul', $informasi->judul) }}" required class="bidang">
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold" for="ringkasan">Ringkasan</label>
            <input id="ringkasan" name="ringkasan" value="{{ old('ringkasan', $informasi->ringkasan) }}" required class="bidang">
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold" for="isi">Isi</label>
            <textarea id="isi" name="isi" required class="bidang min-h-48 py-2">{{ old('isi', $informasi->isi) }}</textarea>
        </div>
        <label class="flex min-h-11 items-center gap-2 text-sm">
            <input type="checkbox" name="terbit" value="1" @checked(old('terbit', $informasi->terbit)) class="h-4 w-4 accent-hijau">
            Terbitkan ke halaman publik
        </label>
        @if ($errors->any())
            <div class="rounded-xl border border-tanah/40 bg-white px-4 py-3 text-sm text-tanah">
                @foreach ($errors->all() as $pesan)<p>{{ $pesan }}</p>@endforeach
            </div>
        @endif
        <div class="flex gap-3">
            <button class="tombol" type="submit">Simpan</button>
            <a href="{{ route('admin.informasi.index') }}" class="tombol-tenang">Batal</a>
        </div>
    </form>
@endsection
