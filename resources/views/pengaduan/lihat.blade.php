@extends('layouts.aplikasi')

@section('judul', 'ADU-'.$pengaduan->id)

@section('isi')
    <p class="text-sm text-tanah">ADU-{{ $pengaduan->id }} · {{ $pengaduan->status->label() }}</p>
    <h1 class="font-serif text-3xl text-hijau-dalam">{{ $pengaduan->judul }}</h1>
    <p class="mt-2 text-sm text-tinta/70">{{ $pengaduan->nama }} · {{ $pengaduan->no_hp }} @if ($pengaduan->email)· {{ $pengaduan->email }}@endif</p>
    <p class="kartu mt-4 text-sm leading-relaxed">{{ $pengaduan->isi }}</p>
    @if (session('status'))
        <p class="mt-4 rounded-xl border border-daun/30 bg-white px-4 py-3 text-sm text-hijau">{{ session('status') }}</p>
    @endif
    <form method="POST" action="{{ route('pengaduan.tanggapan', $pengaduan) }}" class="mt-6 max-w-lg space-y-3">
        @csrf
        <label class="text-sm font-semibold" for="status">Status</label>
        <select id="status" name="status" class="bidang">
            <option value="diproses" @selected(old('status', $pengaduan->status->value) === 'diproses')>Diproses</option>
            <option value="selesai" @selected(old('status', $pengaduan->status->value) === 'selesai')>Selesai</option>
        </select>
        <label class="text-sm font-semibold" for="tanggapan">Tanggapan</label>
        <textarea id="tanggapan" name="tanggapan" required class="bidang min-h-28 py-2">{{ old('tanggapan', $pengaduan->tanggapan) }}</textarea>
        @if ($errors->any())
            <div class="rounded-xl border border-tanah/40 bg-white px-4 py-3 text-sm text-tanah">
                @foreach ($errors->all() as $pesan)<p>{{ $pesan }}</p>@endforeach
            </div>
        @endif
        <button class="tombol" type="submit">Simpan tanggapan</button>
    </form>
@endsection
