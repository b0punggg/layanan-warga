@extends('layouts.aplikasi')

@section('judul', $jenis->nama)

@section('isi')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            @if ($jenis->naskahResmi())
                <p class="text-sm font-semibold text-hijau-dalam">Naskah resmi Kalurahan Sindumartani, kertas F4</p>
            @else
                <p class="text-sm font-semibold text-tanah">Draf, belum dikonfirmasi kalurahan</p>
            @endif
            <h1 class="font-serif text-3xl text-hijau-dalam">{{ $jenis->nama }}</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('operator.templat.word', $jenis) }}" class="tombol">Unduh Word</a>
            <a href="{{ route('operator.templat.index') }}" class="tombol-tenang">Kembali</a>
        </div>
    </div>
    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-tinta/70">Nomor contoh {{ $nomor }}. Data di halaman ini hanya contoh. Blok tanda tangan semua surat sama, termasuk gambar penanda untuk SIMARDA pada berkas Word.</p>
    @if (session('status'))
        <p class="mt-4 rounded-xl border border-daun/30 bg-white px-4 py-3 text-sm text-hijau">{{ session('status') }}</p>
    @endif
    @include('komponen.ringkasan-galat')
    @if ($jenis->isianTerkunci())
        <section class="kartu mt-4 max-w-3xl text-sm leading-relaxed">
            <p>Isian surat ini terkunci supaya susunan resminya tidak berubah.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach (app(\App\Support\IsianSurat::class)->baris($jenis) as $baris)
                    <li>{{ $baris['label'] }}{{ $baris['wajib'] ? '' : ' (tidak wajib)' }}</li>
                @endforeach
            </ul>
        </section>
    @else
        @php
            $field = old('field', $jenis->skema_field_tambahan ?? []);
            $field[] = ['nama' => '', 'label' => '', 'wajib' => false];
        @endphp
        <form method="POST" action="{{ route('operator.templat.simpan', $jenis) }}" class="kartu mt-4 max-w-3xl space-y-4">
            @csrf
            @method('PUT')
            <fieldset class="space-y-3">
                <legend class="text-sm font-semibold">Isian tambahan</legend>
                <p class="text-sm text-tinta/70">Nama isian dipakai di naskah sebagai <span class="font-mono">@{{nama_isian}}</span>. Data diri seperti nama dan NIK tidak ditulis ulang di sini.</p>
                @foreach ($field as $i => $item)
                    <div class="grid gap-2 sm:grid-cols-[8rem_1fr_auto]">
                        <input name="field[{{ $i }}][nama]" value="{{ $item['nama'] ?? '' }}" placeholder="keperluan" class="bidang{{ $errors->has('field.'.$i.'.nama') ? ' bidang-salah' : '' }}">
                        <input name="field[{{ $i }}][label]" value="{{ $item['label'] ?? '' }}" placeholder="Keperluan" class="bidang">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="field[{{ $i }}][wajib]" value="1" @checked($item['wajib'] ?? false)> Wajib</label>
                    </div>
                @endforeach
                @include('komponen.galat', ['nama' => 'field'])
            </fieldset>
            <div>
                <label class="mb-1 block text-sm font-semibold" for="naskah">Naskah surat</label>
                <textarea id="naskah" name="naskah" required class="bidang min-h-64 py-2 font-mono text-sm{{ $errors->has('naskah') ? ' bidang-salah' : '' }}">{{ old('naskah', $jenis->naskah) }}</textarea>
                @include('komponen.galat', ['nama' => 'naskah'])
            </div>
            <button class="tombol" type="submit">Simpan isian</button>
        </form>
    @endif
    @if ($jenis->pernyataan)
        <section class="kartu mt-6 max-w-3xl text-sm leading-relaxed">
            <p>Surat pernyataan ini tanpa kop dan nomor. Isi berkas Word yang diunggah admin yang dikirim ke pemohon.</p>
        </section>
    @else
    <article class="lembar mx-auto mt-6 bg-white shadow-md" style="width: min(100%, 215.9mm); min-height: 330.2mm; padding: 22.5mm 16mm 22.5mm 25mm;">
        @include($jenis->tampilanSurat())
    </article>
    @endif
@endsection
