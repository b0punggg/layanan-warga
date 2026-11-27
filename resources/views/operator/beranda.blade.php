@extends('layouts.aplikasi')

@section('judul', 'Beranda operator')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Meja operator</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-tinta/70">Antrean akun warga dan pengajuan surat. Pemeriksaan berkas dibuka pada tahap berikutnya. Angka di bawah membaca data yang sudah tersimpan.</p>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            'Akun menunggu' => $menungguAkun,
            'Surat diajukan' => $diajukan,
            'Perlu perbaikan' => $perluPerbaikan,
            'Menunggu Lurah' => $menungguLurah,
            'Menunggu unggah TTE' => $menungguUnggah,
        ] as $label => $jumlah)
            <article class="kartu">
                <p class="text-sm text-tinta/70">{{ $label }}</p>
                <p class="mt-2 font-serif text-4xl text-hijau">{{ $jumlah }}</p>
            </article>
        @endforeach
    </div>

    <section class="kartu mt-8">
        <h2 class="font-serif text-xl">Status pengajuan</h2>
        <p class="mt-2 text-sm leading-relaxed text-tinta/70">Draf, diajukan, perlu perbaikan, diverifikasi, disetujui, selesai, atau ditolak. Penolakan dan permintaan perbaikan wajib menyertakan alasan.</p>
    </section>
@endsection
