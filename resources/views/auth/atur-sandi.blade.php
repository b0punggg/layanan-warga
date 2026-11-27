@extends('layouts.tamu')

@section('judul', 'Kata sandi baru')

@section('isi')
    <div class="mx-auto max-w-md">
        <h1 class="font-serif text-3xl text-hijau-dalam">Kata sandi baru</h1>
        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email" class="mb-1 block text-sm font-semibold">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required class="bidang">
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-semibold">Kata sandi baru</label>
                <input id="password" name="password" type="password" required class="bidang">
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-semibold">Ulangi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="bidang">
            </div>
            @if ($errors->any())
                <div class="rounded-xl border border-tanah/40 bg-white px-4 py-3 text-sm text-tanah">
                    @foreach ($errors->all() as $pesan)<p>{{ $pesan }}</p>@endforeach
                </div>
            @endif
            <button class="tombol w-full" type="submit">Simpan</button>
        </form>
    </div>
@endsection
