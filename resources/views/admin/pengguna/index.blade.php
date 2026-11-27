@extends('layouts.aplikasi')

@section('judul', 'Pengguna')

@section('isi')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl text-hijau-dalam">Pengguna</h1>
            <p class="mt-2 text-sm text-tinta/70">Akun staf dibuat di sini. Warga mendaftar sendiri pada tahap berikutnya.</p>
        </div>
        <a href="{{ route('admin.pengguna.create') }}" class="tombol">Tambah staf</a>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-tanah/40 bg-white px-4 py-3 text-sm text-tanah">
            @foreach ($errors->all() as $pesan)
                <p>{{ $pesan }}</p>
            @endforeach
        </div>
    @endif

    <div class="mt-6 overflow-x-auto rounded-2xl border border-garis bg-lembar">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-garis text-tinta/70">
                <tr>
                    <th class="px-4 py-3 font-semibold">Nama</th>
                    <th class="px-4 py-3 font-semibold">Peran</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Masuk terakhir</th>
                    <th class="px-4 py-3 font-semibold"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pengguna as $orang)
                    <tr class="border-b border-garis last:border-0">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ $orang->name }}</p>
                            <p class="text-tinta/70">{{ $orang->email ?? $orang->nik }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $orang->peran->label() }}</td>
                        <td class="px-4 py-3">{{ $orang->status_akun->label() }}</td>
                        <td class="px-4 py-3 text-tinta/70">{{ $orang->terakhir_login?->translatedFormat('d MMM yyyy HH:mm') ?? 'Belum pernah' }}</td>
                        <td class="px-4 py-3">
                            @if ($orang->status_akun === \App\Enums\StatusAkun::Aktif || $orang->status_akun === \App\Enums\StatusAkun::Nonaktif)
                                <form method="POST" action="{{ route('admin.pengguna.status', $orang) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status_akun" value="{{ $orang->status_akun === \App\Enums\StatusAkun::Aktif ? 'nonaktif' : 'aktif' }}">
                                    <button type="submit" class="min-h-11 text-sm font-semibold text-hijau">
                                        {{ $orang->status_akun === \App\Enums\StatusAkun::Aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
