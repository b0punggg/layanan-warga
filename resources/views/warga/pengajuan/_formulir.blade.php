<form method="POST" enctype="multipart/form-data" action="{{ $pengajuan ? route('warga.pengajuan.perbarui', $pengajuan) : route('warga.pengajuan.simpan', $jenis) }}" class="mt-6 max-w-lg space-y-4">
    @csrf
    @if ($pengajuan) @method('PUT') @endif
    @include('komponen.ringkasan-galat')
    @include('pengajuan.isian', [
        'jenis' => $jenis,
        'pemohon' => $pengajuan?->pemohon ?? auth()->user(),
        'tersimpan' => $pengajuan?->data_form ?? [],
    ])
    @foreach ($jenis->daftar_syarat ?? [] as $syarat)
        <div>
            <label class="mb-1 block text-sm font-semibold" for="berkas_{{ $syarat['nama'] }}">{{ $syarat['label'] }}@if (empty($syarat['wajib'])) <span class="font-normal text-tinta/60">(opsional)</span>@endif</label>
            <input id="berkas_{{ $syarat['nama'] }}" name="berkas[{{ $syarat['nama'] }}]" type="file" accept="image/jpeg,image/png,application/pdf" @if($errors->has('berkas.'.$syarat['nama'])) aria-invalid="true" aria-describedby="galat-berkas-{{ $syarat['nama'] }}" @endif class="bidang py-2{{ $errors->has('berkas.'.$syarat['nama']) ? ' bidang-salah' : '' }}">
            @include('komponen.galat', ['nama' => 'berkas.'.$syarat['nama']])
        </div>
    @endforeach
    @if ($jenis->butuh_pengantar_rt && $jenis->mode_pengantar === \App\Enums\ModePengantar::VerifikasiOnline)
        <p class="rounded-xl border border-garis bg-white px-4 py-3 text-sm leading-relaxed">Pengantar diminta ke ketua RT/RW sesuai RT dan RW di profil. Pastikan keduanya sudah benar.</p>
    @endif
    <div class="flex flex-wrap gap-3">
        <button class="tombol-tenang" type="submit" name="aksi" value="draf">Simpan draf</button>
        <button class="tombol" type="submit" name="aksi" value="kirim">Kirim</button>
    </div>
</form>
