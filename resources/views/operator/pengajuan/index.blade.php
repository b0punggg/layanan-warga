@extends('layouts.aplikasi')

@section('judul', 'Antrean')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Antrean pengajuan</h1>
    <form method="GET" class="mt-4 grid gap-3 sm:grid-cols-4">
        <select name="status" class="bidang">
            <option value="">Semua status</option>
            @foreach ($status as $item)
                <option value="{{ $item->value }}" @selected(request('status') === $item->value)>{{ $item->label() }}</option>
            @endforeach
        </select>
        <select name="jenis" class="bidang">
            <option value="">Semua jenis</option>
            @foreach ($jenis as $item)
                <option value="{{ $item->id }}" @selected(request('jenis') == $item->id)>{{ $item->nama }}</option>
            @endforeach
        </select>
        <input type="date" name="dari" value="{{ request('dari') }}" class="bidang">
        <button class="tombol" type="submit">Saring</button>
    </form>
    <div class="mt-4 space-y-2">
        @forelse ($pengajuan as $item)
            <a href="{{ route('operator.pengajuan.lihat', $item) }}" class="kartu flex items-center justify-between text-sm">
                <span>
                    <span class="block font-semibold">{{ $item->pemohon->name }} · {{ $item->jenisLayanan->nama }}</span>
                    <span class="text-tinta/70">{{ $item->kode_tiket }}</span>
                </span>
                <span>{{ $item->status->label() }}</span>
            </a>
        @empty
            <p class="kartu text-sm text-tinta/70">Tidak ada pengajuan pada saringan ini.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $pengajuan->links() }}</div>
@endsection
