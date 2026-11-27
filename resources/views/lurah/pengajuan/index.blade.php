@extends('layouts.aplikasi')

@section('judul', 'Persetujuan')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Menunggu persetujuan</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-tinta/70">Persetujuan di sini belum menempelkan tanda tangan. Penandatanganan dilakukan di SIMARDA, lalu operator mengunggah PDF-nya.</p>
    <form method="POST" action="{{ route('lurah.pengajuan.massal') }}" class="mt-4 space-y-2">
        @csrf
        @forelse ($pengajuan as $item)
            <label class="kartu flex items-center gap-3 text-sm">
                <input type="checkbox" name="pengajuan[]" value="{{ $item->id }}" class="h-4 w-4 accent-hijau">
                <a href="{{ route('lurah.pengajuan.lihat', $item) }}" class="flex-1">
                    <span class="block font-semibold">{{ $item->pemohon->name }} · {{ $item->jenisLayanan->nama }}</span>
                    <span class="text-tinta/70">{{ $item->surat->nomor_surat ?? $item->kode_tiket }}</span>
                </a>
            </label>
        @empty
            <p class="kartu text-sm text-tinta/70">Tidak ada surat yang menunggu.</p>
        @endforelse
        @if ($pengajuan->isNotEmpty())
            <button class="tombol" type="submit">Setujui yang dipilih</button>
        @endif
    </form>
@endsection
