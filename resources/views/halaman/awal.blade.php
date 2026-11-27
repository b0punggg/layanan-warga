@extends('layouts.tamu')

@section('judul', 'Kalurahan Sindumartani')

@section('isi')
    <section class="grid items-center gap-8 md:grid-cols-[1.4fr_0.8fr]">
        <div>
            <p class="text-sm font-semibold tracking-tight text-hijau">Kalurahan Sindumartani</p>
            <h1 class="mt-3 max-w-xl text-4xl font-extrabold leading-tight tracking-tight text-hijau-dalam md:text-5xl">Surat diurus dari rumah.</h1>
            <p class="mt-4 max-w-xl text-lg leading-relaxed text-tinta/75">Ajukan surat Kalurahan Sindumartani, lengkapi berkas jika operator meminta perbaikan, lalu unduh setelah Lurah menyetujui. Warga yang lebih nyaman datang ke kantor tetap dilayani di loket.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('masuk') }}" class="tombol gap-2">@include('komponen.ikon', ['nama' => 'masuk', 'kelas' => 'h-4 w-4']) Masuk untuk mengajukan</a>
                <a href="{{ route('cek.form') }}" class="tombol-tenang gap-2">@include('komponen.ikon', ['nama' => 'cek', 'kelas' => 'h-4 w-4']) Cek keaslian surat</a>
            </div>
        </div>
        <aside class="kartu">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo-sindumartani.png') }}" alt="" width="40" height="40" class="h-10 w-10 rounded-full bg-white object-cover ring-1 ring-black/5">
                <p class="text-sm font-semibold text-hijau">Layanan yang sudah dibuka</p>
            </div>
            <ul class="mt-4 space-y-3 text-sm leading-relaxed">
                @foreach (['usaha' => 'Surat Keterangan Usaha', 'rumah' => 'Surat Keterangan Domisili', 'hati' => 'SKTM Pendidikan', 'sehat' => 'SKTM Kesehatan', 'uang' => 'Surat Keterangan Penghasilan', 'perisai' => 'Pengantar SKCK'] as $ikon => $nama)
                    <li class="flex items-center gap-3 border-b border-garis pb-3 last:border-0 last:pb-0">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-pasir text-hijau">@include('komponen.ikon', ['nama' => $ikon, 'kelas' => 'h-4 w-4'])</span>
                        {{ $nama }}
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 text-sm text-tinta/70">Foto KK tidak wajib. Keterangan penghasilan meminta persetujuan RT/RW di aplikasi. Naskah SKU dan kedua SKTM sudah mengikuti berkas kalurahan. Surat lain masih draf.</p>
            <p class="mt-3 text-sm"><a href="{{ route('informasi.index') }}" class="font-semibold text-hijau">Informasi publik</a> dan <a href="{{ route('pengaduan.buat') }}" class="font-semibold text-hijau">pengaduan</a> dapat diakses tanpa masuk.</p>
        </aside>
    </section>

    <section class="mt-12">
        <h2 class="text-2xl font-extrabold tracking-tight text-hijau-dalam">Alur yang disiapkan</h2>
        <ol class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (['pengajuan' => 'Warga mengisi dan mengunggah syarat', 'verifikasi' => 'Operator memeriksa berkas', 'ttd' => 'Lurah menyetujui surat', 'arsip' => 'Operator mengunggah PDF bertanda tangan'] as $ikon => $langkah)
                <li class="kartu text-sm leading-relaxed">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-pasir text-hijau">@include('komponen.ikon', ['nama' => $ikon, 'kelas' => 'h-4 w-4'])</span>
                    <p class="mt-3">{{ $langkah }}</p>
                </li>
            @endforeach
        </ol>
    </section>
@endsection
