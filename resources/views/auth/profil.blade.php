@extends('layouts.tamu')

@section('judul', 'Lengkapi profil')

@section('isi')
    <style>.tersembunyi{display:none}</style>
    <div class="mx-auto max-w-lg">
        <h1 class="font-serif text-3xl text-hijau-dalam">Periksa data</h1>
        <p class="mt-2 text-sm leading-relaxed text-tinta/70">
            @if ($saran['cocok'])
                Nama cocok dengan catatan kalurahan. Alamat diisi dari data itu. Tanggal lahir dan jenis kelamin dihitung dari NIK. Unggah foto KTP, periksa isian, lalu buat kata sandi.
            @else
                Data belum ada di catatan kalurahan. Unggah foto KTP dan isi alamat. Operator akan memeriksa secara manual.
            @endif
        </p>
        <p class="mt-2 text-sm">{{ $otp->nama }} · NIK {{ $otp->nik }}</p>
        @if ($saran['cocok'] && ($saran['dukuh'] || $saran['rt']))
            <p class="mt-1 text-sm text-tinta/70">Wilayah: {{ $saran['dukuh'] ?: 'belum terurai' }} · RT {{ $saran['rt'] ?: '—' }} · RW {{ $saran['rw'] ?: '—' }}</p>
        @endif

        <form method="POST" action="{{ route('daftar.profil.simpan') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf
            @include('komponen.ringkasan-galat')
            <div>
                <label for="foto_ktp" class="mb-1 block text-sm font-semibold">Foto KTP</label>
                <input id="foto_ktp" name="foto_ktp" type="file" accept="image/jpeg,image/png" required @if($errors->has('foto_ktp')) aria-invalid="true" aria-describedby="galat-foto_ktp" @endif class="bidang py-2{{ $errors->has('foto_ktp') ? ' bidang-salah' : '' }}">
                <img id="pratinjau-ktp" alt="" class="tersembunyi mt-3 max-h-48 rounded-2xl border border-garis">
                <p id="pesan-foto" class="mt-2 text-sm text-tinta/70"></p>
                @include('komponen.galat', ['nama' => 'foto_ktp'])
            </div>

            <div>
                <label for="alamat_lengkap" class="mb-1 block text-sm font-semibold">{{ $saran['cocok'] ? 'Alamat' : 'Alamat lengkap' }}</label>
                <textarea id="alamat_lengkap" name="alamat_lengkap" @required(! $saran['cocok']) @if($errors->has('alamat_lengkap')) aria-invalid="true" aria-describedby="galat-alamat_lengkap" @endif class="bidang min-h-24 py-2{{ $errors->has('alamat_lengkap') ? ' bidang-salah' : '' }}">{{ old('alamat_lengkap', $saran['alamat_lengkap']) }}</textarea>
                @include('komponen.galat', ['nama' => 'alamat_lengkap'])
            </div>

            @if (old('tanggal_lahir', $saran['tanggal_lahir']) || old('jenis_kelamin', $saran['jenis_kelamin']))
                <div>
                    <label for="tanggal_lahir" class="mb-1 block text-sm font-semibold">Tanggal lahir</label>
                    <input id="tanggal_lahir" name="tanggal_lahir" type="date" value="{{ old('tanggal_lahir', $saran['tanggal_lahir']) }}" @if($errors->has('tanggal_lahir')) aria-invalid="true" aria-describedby="galat-tanggal_lahir" @endif class="bidang{{ $errors->has('tanggal_lahir') ? ' bidang-salah' : '' }}">
                    @include('komponen.galat', ['nama' => 'tanggal_lahir'])
                </div>
                <div>
                    <label for="jenis_kelamin" class="mb-1 block text-sm font-semibold">Jenis kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin" @if($errors->has('jenis_kelamin')) aria-invalid="true" aria-describedby="galat-jenis_kelamin" @endif class="bidang{{ $errors->has('jenis_kelamin') ? ' bidang-salah' : '' }}">
                        <option value="">Pilih</option>
                        @foreach (['Laki-laki', 'Perempuan'] as $pilihan)
                            <option value="{{ $pilihan }}" @selected(old('jenis_kelamin', $saran['jenis_kelamin']) === $pilihan)>{{ $pilihan }}</option>
                        @endforeach
                    </select>
                    @include('komponen.galat', ['nama' => 'jenis_kelamin'])
                </div>
            @endif

            @php
                $ekstra = ['tempat_lahir' => 'Tempat lahir', 'agama' => 'Agama', 'status_kawin' => 'Status kawin', 'pekerjaan' => 'Pekerjaan'];
                $adaEkstra = collect(array_keys($ekstra))->contains(fn (string $nama): bool => filled(old($nama)));
            @endphp
            <div id="isian-foto" class="{{ $adaEkstra ? '' : 'tersembunyi' }} space-y-4">
                <p class="text-sm text-tinta/70">Terisi dari foto. Betulkan jika ada yang salah.</p>
                @foreach ($ekstra as $nama => $label)
                    <div data-ekstra="{{ $nama }}" class="{{ filled(old($nama)) ? '' : 'tersembunyi' }}">
                        <label for="{{ $nama }}" class="mb-1 block text-sm font-semibold">{{ $label }}</label>
                        <input id="{{ $nama }}" name="{{ $nama }}" value="{{ old($nama) }}" @if($errors->has($nama)) aria-invalid="true" aria-describedby="galat-{{ $nama }}" @endif class="bidang{{ $errors->has($nama) ? ' bidang-salah' : '' }}">
                        @include('komponen.galat', ['nama' => $nama])
                    </div>
                @endforeach
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-semibold">Kata sandi</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" @if($errors->has('password')) aria-invalid="true" aria-describedby="galat-password" @endif class="bidang{{ $errors->has('password') ? ' bidang-salah' : '' }}">
                @include('komponen.galat', ['nama' => 'password'])
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-semibold">Ulangi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="bidang">
            </div>
            <button class="tombol w-full" type="submit">Kirim pendaftaran</button>
        </form>
    </div>
    <script>
        const berkas = document.getElementById('foto_ktp');
        const gambar = document.getElementById('pratinjau-ktp');
        const pesan = document.getElementById('pesan-foto');
        berkas.addEventListener('change', async () => {
            const file = berkas.files && berkas.files[0];
            if (!file) return;
            gambar.src = URL.createObjectURL(file);
            gambar.classList.remove('tersembunyi');
            pesan.textContent = 'Membaca foto…';
            const data = new FormData();
            data.append('foto_ktp', file);
            data.append('_token', '{{ csrf_token() }}');
            try {
                const jawab = await fetch('{{ route('daftar.profil.baca') }}', {
                    method: 'POST',
                    body: data,
                    headers: { 'Accept': 'application/json' },
                });
                const isi = await jawab.json();
                pesan.textContent = isi.peringatan || '';
                const kotak = document.getElementById('isian-foto');
                Object.entries(isi.isian || {}).forEach(([nama, nilai]) => {
                    const bidang = document.querySelector('[name="' + nama + '"]');
                    if (!bidang || !nilai) return;
                    bidang.value = nilai;
                    const bungkus = bidang.closest('[data-ekstra]');
                    if (bungkus) {
                        bungkus.classList.remove('tersembunyi');
                        kotak.classList.remove('tersembunyi');
                    }
                });
            } catch (e) {
                pesan.textContent = 'Foto belum terbaca. Periksa isian di bawah, lalu kirim.';
            }
        });
    </script>
@endsection
