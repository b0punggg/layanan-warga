@extends('layouts.aplikasi')

@section('judul', 'Pesan')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Pesan status</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-tinta/70">Perubahan status tersimpan di sini. Jika gateway WhatsApp sudah diisi, pesan yang sama juga dikirim ke nomor HP. Jika WhatsApp gagal dan akun punya email, salinan dikirim lewat email.</p>
    <div class="mt-6 space-y-3">
        @forelse ($pesan as $item)
            <article class="kartu">
                <p class="text-sm text-tinta/60">{{ $item->waktu?->translatedFormat('d F Y H:i') }} · {{ $item->kanal }}</p>
                <p class="mt-2 text-sm leading-relaxed">{{ $item->isi }}</p>
            </article>
        @empty
            <p class="kartu text-sm text-tinta/70">Belum ada pesan.</p>
        @endforelse
    </div>
@endsection
