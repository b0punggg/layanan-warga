@extends('layouts.aplikasi')

@section('judul', 'Pratinjau impor')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Pratinjau {{ $nama }}</h1>
    <p class="mt-2 text-sm">Siap ditambah {{ $pratinjau['tambah'] }}, diperbarui {{ $pratinjau['perbarui'] }}, gagal {{ $pratinjau['gagal'] }}.</p>
    <div class="mt-4 overflow-x-auto rounded-2xl border border-garis bg-lembar">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-garis"><tr><th class="px-4 py-3">Baris</th><th class="px-4 py-3">NIK</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Hasil</th></tr></thead>
            <tbody>
                @foreach (array_slice($pratinjau['baris'], 0, 40) as $baris)
                    <tr class="border-b border-garis">
                        <td class="px-4 py-3">{{ $baris['baris'] }}</td>
                        <td class="px-4 py-3">{{ $baris['nik'] }}</td>
                        <td class="px-4 py-3">{{ $baris['nama'] }}</td>
                        <td class="px-4 py-3">{{ $baris['mode'] === 'gagal' ? implode(', ', $baris['alasan']) : ($baris['alamat_terurai'] ? $baris['dukuh'].' RT '.$baris['rt'].'/RW '.$baris['rw'] : 'Alamat belum terurai') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex flex-wrap gap-3">
        @if ($pratinjau['gagal'] > 0)
            <a class="tombol-tenang" href="{{ route('admin.impor.gagal', $token) }}">Unduh baris gagal</a>
        @endif
        <form method="POST" action="{{ route('admin.impor.simpan') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button class="tombol" type="submit">Simpan data valid</button>
        </form>
    </div>
@endsection
