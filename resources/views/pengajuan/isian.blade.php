@php
    $isian = app(\App\Support\IsianSurat::class);
    $baris = $isian->baris($jenis);
    $awal = $isian->awal($pemohon);
    $tersimpan = $tersimpan ?? [];
@endphp
@foreach ($baris as $field)
    @php
        $nama = $field['nama'];
        $nilai = old('data.'.$nama, $isian->nilai($nama, $tersimpan, $awal));
        $panjang = in_array($nama, ['alamat', 'alamat_usaha', 'alamat_tujuan'], true);
    @endphp
    <div>
        <label class="mb-1 block text-sm font-semibold" for="data_{{ $nama }}">{{ $field['label'] }}</label>
        @if ($nama === 'jenis_kelamin')
            <select id="data_{{ $nama }}" name="data[{{ $nama }}]" @if($errors->has('data.'.$nama)) aria-invalid="true" aria-describedby="galat-data-{{ $nama }}" @endif class="bidang{{ $errors->has('data.'.$nama) ? ' bidang-salah' : '' }}">
                <option value="">Pilih</option>
                @foreach (['Laki-laki', 'Perempuan'] as $pilihan)
                    <option value="{{ $pilihan }}" @selected($nilai === $pilihan)>{{ $pilihan }}</option>
                @endforeach
            </select>
        @elseif (($field['tipe'] ?? '') === 'pilihan')
            <select id="data_{{ $nama }}" name="data[{{ $nama }}]" @if($errors->has('data.'.$nama)) aria-invalid="true" aria-describedby="galat-data-{{ $nama }}" @endif class="bidang{{ $errors->has('data.'.$nama) ? ' bidang-salah' : '' }}">
                <option value="">Pilih</option>
                @foreach ($field['pilihan'] ?? [] as $pilihan)
                    <option value="{{ $pilihan['nilai'] }}" @selected($nilai === $pilihan['nilai'])>{{ $pilihan['label'] }}</option>
                @endforeach
            </select>
        @elseif ($panjang)
            <textarea id="data_{{ $nama }}" name="data[{{ $nama }}]" rows="2" @if($errors->has('data.'.$nama)) aria-invalid="true" aria-describedby="galat-data-{{ $nama }}" @endif class="bidang py-2{{ $errors->has('data.'.$nama) ? ' bidang-salah' : '' }}">{{ $nilai }}</textarea>
        @else
            <input id="data_{{ $nama }}" name="data[{{ $nama }}]" value="{{ $nilai }}" @if($errors->has('data.'.$nama)) aria-invalid="true" aria-describedby="galat-data-{{ $nama }}" @endif class="bidang{{ $errors->has('data.'.$nama) ? ' bidang-salah' : '' }}">
        @endif
        @include('komponen.galat', ['nama' => 'data.'.$nama])
    </div>
@endforeach
