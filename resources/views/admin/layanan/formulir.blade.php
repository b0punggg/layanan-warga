@extends('layouts.aplikasi')

@section('judul', $jenis->exists ? 'Ubah layanan' : 'Jenis layanan baru')

@section('isi')
    @php
        $kunci = $jenis->exists && $jenis->pengajuan()->exists();
        $syarat = old('syarat', $jenis->daftar_syarat ?? []);
        $field = old('field', $jenis->skema_field_tambahan ?? []);
        $syarat[] = ['nama' => '', 'label' => '', 'wajib' => false];
        $field[] = ['nama' => '', 'label' => '', 'wajib' => false];
    @endphp
    <h1 class="font-serif text-3xl text-hijau-dalam">{{ $jenis->exists ? 'Ubah jenis layanan' : 'Jenis layanan baru' }}</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-tinta/70">Naskah memakai penanda seperti <span class="font-mono">@{{nama}}</span>, <span class="font-mono">@{{nik}}</span>, <span class="font-mono">@{{alamat}}</span>, <span class="font-mono">@{{nomor}}</span>, dan nama isian tambahan. HTML tidak diproses.</p>

    <form method="POST" action="{{ $jenis->exists ? route('admin.layanan.perbarui', $jenis) : route('admin.layanan.simpan') }}" enctype="multipart/form-data" class="mt-6 max-w-3xl space-y-4">
        @csrf
        @if ($jenis->exists) @method('PUT') @endif
        <div>
            <label class="mb-1 block text-sm font-semibold" for="nama">Nama</label>
            <input id="nama" name="nama" value="{{ old('nama', $jenis->nama) }}" required class="bidang">
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold" for="kode">Kode</label>
                <input id="kode" name="kode" value="{{ old('kode', $jenis->kode) }}" @disabled($kunci) required class="bidang">
                @if ($kunci)<input type="hidden" name="kode" value="{{ $jenis->kode }}">@endif
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold" for="estimasi_hari">Perkiraan hari</label>
                <input id="estimasi_hari" name="estimasi_hari" type="number" min="1" max="30" value="{{ old('estimasi_hari', $jenis->estimasi_hari ?? 3) }}" required class="bidang">
            </div>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold" for="deskripsi">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" class="bidang min-h-20 py-2">{{ old('deskripsi', $jenis->deskripsi) }}</textarea>
        </div>
        <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="aktif" value="1" @checked(old('aktif', $jenis->aktif ?? true)) class="h-4 w-4 accent-hijau"> Aktif, tampil untuk warga</label>
        <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="pernyataan" value="1" @checked(old('pernyataan', $jenis->pernyataan)) class="h-4 w-4 accent-hijau"> Surat pernyataan, tanpa kop dan nomor</label>
        <div>
            <label class="mb-1 block text-sm font-semibold" for="template_word">Berkas Word surat pernyataan</label>
            <input id="template_word" type="file" name="template_word" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="bidang py-2">
            <p class="mt-1 text-sm text-tinta/70">Ketik penanda seperti <span class="font-mono">@{{nama}}</span> dan <span class="font-mono">@{{keperluan}}</span> sekaligus. Penanda itu menjadi isian pemohon. Kosongkan bila bukan surat pernyataan{{ $jenis->berkas_template ? ', atau bila berkas yang sudah ada tetap dipakai' : '' }}.</p>
            @include('komponen.galat', ['nama' => 'template_word'])
        </div>
        <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="butuh_pengantar_rt" value="1" @checked(old('butuh_pengantar_rt', $jenis->butuh_pengantar_rt)) class="h-4 w-4 accent-hijau"> Butuh pengantar RT/RW</label>
        <div>
            <label class="mb-1 block text-sm font-semibold" for="mode_pengantar">Cara pengantar</label>
            <select id="mode_pengantar" name="mode_pengantar" class="bidang">
                @foreach (\App\Enums\ModePengantar::cases() as $mode)
                    <option value="{{ $mode->value }}" @selected(old('mode_pengantar', $jenis->mode_pengantar?->value) === $mode->value)>{{ $mode->label() }}</option>
                @endforeach
            </select>
        </div>

        <fieldset class="space-y-3">
            <legend class="text-sm font-semibold">Syarat berkas</legend>
            @foreach ($syarat as $i => $item)
                <div class="grid gap-2 sm:grid-cols-[8rem_1fr_auto]">
                    <input name="syarat[{{ $i }}][nama]" value="{{ $item['nama'] ?? '' }}" placeholder="ktp" class="bidang">
                    <input name="syarat[{{ $i }}][label]" value="{{ $item['label'] ?? '' }}" placeholder="Foto KTP" class="bidang">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="syarat[{{ $i }}][wajib]" value="1" @checked($item['wajib'] ?? false)> Wajib</label>
                </div>
            @endforeach
        </fieldset>

        <fieldset class="space-y-3">
            <legend class="text-sm font-semibold">Isian tambahan</legend>
            @foreach ($field as $i => $item)
                <div class="grid gap-2 sm:grid-cols-[8rem_1fr_auto]">
                    <input name="field[{{ $i }}][nama]" value="{{ $item['nama'] ?? '' }}" placeholder="keperluan" class="bidang">
                    <input name="field[{{ $i }}][label]" value="{{ $item['label'] ?? '' }}" placeholder="Keperluan" class="bidang">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="field[{{ $i }}][wajib]" value="1" @checked($item['wajib'] ?? false)> Wajib</label>
                </div>
            @endforeach
        </fieldset>

        <div>
            <label class="mb-1 block text-sm font-semibold" for="naskah">Naskah surat</label>
            <textarea id="naskah" name="naskah" class="bidang min-h-64 py-2 font-mono text-sm">{{ old('naskah', $jenis->naskah) }}</textarea>
            @if ($jenis->exists && blank($jenis->naskah))
                <p class="mt-1 text-sm text-tinta/70">Kosongkan bila surat ini masih memakai berkas template yang sudah ada.</p>
            @endif
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-tanah/40 bg-white px-4 py-3 text-sm text-tanah">
                @foreach ($errors->all() as $pesan)<p>{{ $pesan }}</p>@endforeach
            </div>
        @endif
        <div class="flex gap-3">
            <button class="tombol" type="submit">Simpan</button>
            <a href="{{ route('admin.layanan.index') }}" class="tombol-tenang">Batal</a>
        </div>
    </form>
@endsection
