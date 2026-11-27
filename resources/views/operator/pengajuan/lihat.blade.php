@extends('layouts.aplikasi')

@section('judul', $pengajuan->kode_tiket)

@section('isi')
    <p class="text-sm text-tanah">{{ $pengajuan->status->label() }}</p>
    <h1 class="font-serif text-3xl text-hijau-dalam">{{ $pengajuan->pemohon->name }}</h1>
    <p class="mt-1 text-sm text-tinta/70">{{ $pengajuan->jenisLayanan->nama }} · {{ $pengajuan->kode_tiket }}</p>
    @if ($pengajuan->status_pengantar_rt)
        <p class="mt-1 text-sm">Pengantar RT/RW: {{ $pengajuan->status_pengantar_rt->label() }}</p>
    @endif
    <dl class="kartu mt-4 grid gap-2 text-sm sm:grid-cols-2">
        @foreach ($pengajuan->data_form ?? [] as $kunci => $nilai)
            <div><dt class="font-semibold">{{ str_replace('_', ' ', $kunci) }}</dt><dd>{{ $nilai }}</dd></div>
        @endforeach
    </dl>
    <ul class="mt-4 space-y-2 text-sm">
        @foreach ($pengajuan->berkas as $berkas)
            <li><a class="font-semibold text-hijau" href="{{ route('berkas.syarat', $berkas) }}">{{ $berkas->nama_syarat }}</a></li>
        @endforeach
        @if ($pengajuan->berkas_pengantar_path)
            <li><a class="font-semibold text-hijau" href="{{ route('berkas.pengantar', $pengajuan) }}">Pengantar RT/RW</a></li>
        @endif
    </ul>
    @if ($pengajuan->status === \App\Enums\StatusPengajuan::Diajukan)
        <form method="POST" action="{{ route('operator.pengajuan.sunting', $pengajuan) }}" class="mt-6 max-w-lg space-y-3">
            @csrf
            @method('PUT')
            @include('pengajuan.isian', [
                'jenis' => $pengajuan->jenisLayanan,
                'pemohon' => $pengajuan->pemohon,
                'tersimpan' => $pengajuan->data_form ?? [],
            ])
            <button class="tombol-tenang" type="submit">Simpan suntingan draf</button>
        </form>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <form method="POST" action="{{ route('operator.pengajuan.perbaikan', $pengajuan) }}" class="kartu space-y-3">
                @csrf
                <label class="text-sm font-semibold">Minta perbaikan</label>
                <textarea name="catatan" required class="bidang min-h-24 py-2" placeholder="Alasan yang perlu dilengkapi"></textarea>
                <button class="tombol-tenang" type="submit">Kembalikan ke warga</button>
            </form>
            <form method="POST" action="{{ route('operator.pengajuan.tolak', $pengajuan) }}" class="kartu space-y-3">
                @csrf
                <label class="text-sm font-semibold">Tolak</label>
                <textarea name="catatan" required class="bidang min-h-24 py-2" placeholder="Alasan penolakan"></textarea>
                <button class="tombol" type="submit">Tolak pengajuan</button>
            </form>
        </div>
        @if ($pengajuan->jenisLayanan->pernyataan)
            <form method="POST" action="{{ route('operator.pengajuan.format.kirim', $pengajuan) }}" class="kartu mt-6 max-w-lg space-y-3">
                @csrf
                <h2 class="font-serif text-xl">Kirim format ke pemohon</h2>
                <p class="text-sm leading-relaxed text-tinta/70">Berkas ini tanpa kop dan nomor. Pemohon menandatangani sendiri. Tulis pesan yang ikut terkirim.</p>
                <a class="tombol-tenang" href="{{ route('operator.pengajuan.format', $pengajuan) }}">Unduh pratinjau Word</a>
                <div>
                    <label class="mb-1 block text-sm font-semibold" for="pesan">Pesan untuk pemohon</label>
                    <textarea id="pesan" name="pesan" required maxlength="1000" class="bidang min-h-24 py-2{{ $errors->has('pesan') ? ' bidang-salah' : '' }}" placeholder="Contoh: Silakan cetak, tanda tangani, lalu bawa ke kantor.">{{ old('pesan') }}</textarea>
                    @include('komponen.galat', ['nama' => 'pesan'])
                </div>
                <button class="tombol" type="submit">Kirim ke pemohon</button>
            </form>
        @else
        <form method="POST" action="{{ route('operator.pengajuan.kirim', $pengajuan) }}" class="mt-4 max-w-lg space-y-3">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-semibold" for="nomor_surat">Nomor surat</label>
                <input id="nomor_surat" name="nomor_surat" value="{{ old('nomor_surat', $usulanNomor) }}" required maxlength="100" @if($errors->has('nomor_surat')) aria-invalid="true" aria-describedby="galat-nomor_surat" @endif class="bidang{{ $errors->has('nomor_surat') ? ' bidang-salah' : '' }}">
                @include('komponen.galat', ['nama' => 'nomor_surat'])
                <p class="mt-1 text-sm text-tinta/70">Nomor usulan bisa diubah dulu agar sesuai buku nomor, sebelum diajukan ke Lurah.</p>
            </div>
            <button class="tombol" type="submit">Ajukan ke Lurah</button>
        </form>
        @endif
    @endif
    @if ($pengajuan->status === \App\Enums\StatusPengajuan::Disetujui && $pengajuan->surat)
        <section class="kartu mt-6 max-w-lg space-y-4">
            <h2 class="font-serif text-xl">Naskah untuk SIMARDA</h2>
            <p class="text-sm leading-relaxed text-tinta/70">Unduh Word ini dan unggah ke SIMARDA dengan nomor yang sama. Gambar penanda di blok tanda tangan jangan dihapus. SIMARDA menggantinya dengan segel BSrE. Setelah itu unduh PDF bersegel dari SIMARDA, lalu unggah di bawah ini agar terkirim ke warga.</p>
            <div class="flex flex-wrap gap-3">
                <a class="tombol" href="{{ route('operator.pengajuan.naskah.word', $pengajuan) }}">Unduh naskah Word</a>
                <a class="tombol-tenang" href="{{ route('operator.pengajuan.naskah', $pengajuan) }}">Unduh naskah PDF</a>
            </div>
            <form method="POST" action="{{ route('operator.pengajuan.terbit', $pengajuan) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <label class="block text-sm font-semibold" for="berkas_tte">PDF bertanda tangan dari SIMARDA</label>
                <input id="berkas_tte" type="file" name="berkas_tte" accept="application/pdf" required @if($errors->has('berkas_tte')) aria-invalid="true" aria-describedby="galat-berkas_tte" @endif class="bidang py-2{{ $errors->has('berkas_tte') ? ' bidang-salah' : '' }}">
                @include('komponen.galat', ['nama' => 'berkas_tte'])
                <div>
                    <label class="mb-1 block text-sm font-semibold" for="pesan">Pesan untuk pemohon</label>
                    <textarea id="pesan" name="pesan" maxlength="1000" class="bidang min-h-24 py-2{{ $errors->has('pesan') ? ' bidang-salah' : '' }}" placeholder="Pesan yang ikut terkirim bersama surat">{{ old('pesan') }}</textarea>
                    @include('komponen.galat', ['nama' => 'pesan'])
                </div>
                <button class="tombol" type="submit">Kirim ke warga</button>
            </form>
        </section>
    @endif
    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-tanah/40 bg-white px-4 py-3 text-sm text-tanah">
            @foreach ($errors->all() as $pesan)<p>{{ $pesan }}</p>@endforeach
        </div>
    @endif
@endsection
