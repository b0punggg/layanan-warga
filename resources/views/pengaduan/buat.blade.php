@extends('layouts.tamu')

@section('judul', 'Pengaduan')

@section('isi')
    <div class="mx-auto max-w-lg">
        <h1 class="font-serif text-3xl text-hijau-dalam">Sampaikan pengaduan</h1>
        <p class="mt-2 text-sm leading-relaxed text-tinta/70">Aduan dibaca operator kalurahan. Jangan menulis kata sandi atau foto KTP di sini.</p>
        @if (session('status'))
            <p class="mt-4 rounded-xl border border-daun/30 bg-white px-4 py-3 text-sm text-hijau">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('pengaduan.simpan') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-semibold" for="nama">Nama</label>
                <input id="nama" name="nama" value="{{ $nama }}" required class="bidang">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold" for="no_hp">Nomor HP</label>
                <input id="no_hp" name="no_hp" value="{{ $noHp }}" required class="bidang">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold" for="email">Email, bila ada</label>
                <input id="email" name="email" type="email" value="{{ $email }}" class="bidang">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold" for="judul">Judul</label>
                <input id="judul" name="judul" value="{{ old('judul') }}" required class="bidang">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold" for="isi">Uraian</label>
                <textarea id="isi" name="isi" required class="bidang min-h-32 py-2">{{ old('isi') }}</textarea>
            </div>
            <label class="flex min-h-11 items-start gap-2 text-sm">
                <input type="checkbox" name="setuju" value="1" @checked(old('setuju')) class="mt-1 h-4 w-4 accent-hijau">
                Saya mengirim data ini agar ditindaklanjuti kalurahan.
            </label>
            @if ($errors->any())
                <div class="rounded-xl border border-tanah/40 bg-white px-4 py-3 text-sm text-tanah">
                    @foreach ($errors->all() as $pesan)<p>{{ $pesan }}</p>@endforeach
                </div>
            @endif
            <button class="tombol" type="submit">Kirim aduan</button>
        </form>
    </div>
@endsection
