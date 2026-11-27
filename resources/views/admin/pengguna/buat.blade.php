@extends('layouts.aplikasi')

@section('judul', 'Tambah staf')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Tambah staf</h1>
    <form method="POST" action="{{ route('admin.pengguna.store') }}" class="mt-6 max-w-lg space-y-4">
        @csrf
        <div>
            <label for="name" class="mb-1 block text-sm font-semibold">Nama</label>
            <input id="name" name="name" value="{{ old('name') }}" required class="bidang">
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-semibold">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="bidang">
        </div>
        <div>
            <label for="peran" class="mb-1 block text-sm font-semibold">Peran</label>
            <select id="peran" name="peran" required class="bidang">
                @foreach ($peranStaf as $peran)
                    <option value="{{ $peran->value }}" @selected(old('peran') === $peran->value)>{{ $peran->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="no_hp" class="mb-1 block text-sm font-semibold">Nomor HP</label>
            <input id="no_hp" name="no_hp" value="{{ old('no_hp') }}" class="bidang">
        </div>
        <div>
            <label for="dukuh" class="mb-1 block text-sm font-semibold">Dukuh, untuk akun RT/RW</label>
            <input id="dukuh" name="dukuh" value="{{ old('dukuh') }}" class="bidang">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="rt" class="mb-1 block text-sm font-semibold">RT</label>
                <input id="rt" name="rt" value="{{ old('rt') }}" class="bidang">
            </div>
            <div>
                <label for="rw" class="mb-1 block text-sm font-semibold">RW</label>
                <input id="rw" name="rw" value="{{ old('rw') }}" class="bidang">
            </div>
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-semibold">Kata sandi</label>
            <input id="password" name="password" type="password" required class="bidang">
        </div>
        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-semibold">Ulangi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="bidang">
        </div>
        @if ($errors->any())
            <div class="rounded-xl border border-tanah/40 bg-white px-4 py-3 text-sm text-tanah">
                @foreach ($errors->all() as $pesan)
                    <p>{{ $pesan }}</p>
                @endforeach
            </div>
        @endif
        <div class="flex gap-3">
            <button type="submit" class="tombol">Simpan</button>
            <a href="{{ route('admin.pengguna.index') }}" class="tombol-tenang">Batal</a>
        </div>
    </form>
@endsection
