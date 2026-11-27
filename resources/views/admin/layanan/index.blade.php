@extends('layouts.aplikasi')

@section('judul', 'Jenis layanan')

@section('isi')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl text-hijau-dalam">Jenis layanan</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-tinta/70">Layanan yang dinonaktifkan tidak tampil untuk warga. Pengajuan yang sudah masuk tetap diproses.</p>
        </div>
        <a href="{{ route('admin.layanan.buat') }}" class="tombol">Jenis baru</a>
    </div>
    @if (session('status'))
        <p class="mt-4 rounded-xl border border-daun/30 bg-white px-4 py-3 text-sm text-hijau">{{ session('status') }}</p>
    @endif

    <div class="mt-6 space-y-4">
        @forelse ($layanan as $item)
            <article class="kartu">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-tanah">{{ $item->kode }}</p>
                        <h2 class="font-serif text-2xl">{{ $item->nama }}</h2>
                        <p class="mt-1 text-sm text-tinta/70">{{ $item->deskripsi }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold {{ $item->aktif ? 'text-hijau' : 'text-tanah' }}">{{ $item->aktif ? 'Aktif' : 'Nonaktif' }}</span>
                        @if ($item->pernyataan)<span class="text-sm font-semibold text-tanah">Surat pernyataan</span>@endif
                        <form method="POST" action="{{ route('admin.layanan.status', $item) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tombol-tenang">{{ $item->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </form>
                        <a href="{{ route('admin.layanan.ubah', $item) }}" class="tombol-tenang">Ubah</a>
                        <a href="{{ route('admin.templat', $item->kode) }}" class="tombol-tenang">Lihat draf surat</a>
                    </div>
                </div>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-semibold">Pengantar RT/RW</dt>
                        <dd class="text-tinta/70">{{ $item->butuh_pengantar_rt ? $item->mode_pengantar->label() : 'Tidak perlu' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Perkiraan selesai</dt>
                        <dd class="text-tinta/70">{{ $item->estimasi_hari }} hari</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Syarat berkas</dt>
                        <dd class="text-tinta/70">{{ collect($item->daftar_syarat)->pluck('label')->join(', ') }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Isian tambahan</dt>
                        <dd class="text-tinta/70">{{ collect($item->skema_field_tambahan)->pluck('label')->join(', ') }}</dd>
                    </div>
                </dl>
            </article>
        @empty
            <p class="kartu text-sm text-tinta/70">Belum ada jenis layanan. Jalankan pengisian data awal.</p>
        @endforelse
    </div>
@endsection
