@extends('layouts.aplikasi')

@section('judul', 'Tinjau pengantar')

@section('isi')
    <p class="text-sm text-tanah">{{ $pengajuan->kode_tiket }}</p>
    <h1 class="font-serif text-3xl text-hijau-dalam">{{ $pengajuan->pemohon->name }}</h1>
    <p class="mt-1 text-sm text-tinta/70">{{ $pengajuan->jenisLayanan->nama }}</p>
    <dl class="kartu mt-4 grid gap-2 text-sm sm:grid-cols-2">
        <div><dt class="font-semibold">NIK</dt><dd>{{ $pengajuan->pemohon->nik }}</dd></div>
        <div><dt class="font-semibold">Alamat</dt><dd>{{ $pengajuan->pemohon->profilWarga->alamat_lengkap }}</dd></div>
        <div><dt class="font-semibold">RT/RW</dt><dd>{{ $pengajuan->pemohon->profilWarga->rt }}/{{ $pengajuan->pemohon->profilWarga->rw }}</dd></div>
        @foreach ($pengajuan->data_form ?? [] as $kunci => $nilai)
            <div><dt class="font-semibold">{{ str_replace('_', ' ', $kunci) }}</dt><dd>{{ $nilai }}</dd></div>
        @endforeach
    </dl>
    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-tanah/40 bg-white px-4 py-3 text-sm text-tanah">
            @foreach ($errors->all() as $pesan)<p>{{ $pesan }}</p>@endforeach
        </div>
    @endif
    @if ($pengajuan->status_pengantar_rt === \App\Enums\StatusPengantarRt::MenungguRt)
        <form method="POST" action="{{ route('rt.pengajuan.setujui', $pengajuan) }}" class="mt-6">
            @csrf
            <button class="tombol" type="submit">Setujui pengantar</button>
        </form>
        <form method="POST" action="{{ route('rt.pengajuan.tolak', $pengajuan) }}" class="mt-4 max-w-lg space-y-3">
            @csrf
            <label class="text-sm font-semibold" for="catatan">Alasan jika ditolak</label>
            <textarea id="catatan" name="catatan" required class="bidang min-h-24 py-2"></textarea>
            <button class="tombol-tenang" type="submit">Tolak dan kembalikan ke warga</button>
        </form>
    @endif
@endsection
