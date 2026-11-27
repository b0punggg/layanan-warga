@extends('layouts.aplikasi')

@section('judul', 'Audit')

@section('isi')
    <h1 class="font-serif text-3xl text-hijau-dalam">Jejak aktivitas</h1>
    <p class="mt-2 text-sm text-tinta/70">Masuk, keluar, dan perubahan akun tercatat di sini. Akses berkas sensitif akan menyusul.</p>

    <div class="mt-6 overflow-x-auto rounded-2xl border border-garis bg-lembar">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-garis text-tinta/70">
                <tr>
                    <th class="px-4 py-3 font-semibold">Waktu</th>
                    <th class="px-4 py-3 font-semibold">Pengguna</th>
                    <th class="px-4 py-3 font-semibold">Aksi</th>
                    <th class="px-4 py-3 font-semibold">Objek</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($audit as $jejak)
                    <tr class="border-b border-garis last:border-0">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $jejak->waktu->translatedFormat('d MMM yyyy HH:mm') }}</td>
                        <td class="px-4 py-3">{{ $jejak->user?->name ?? 'Sistem' }}</td>
                        <td class="px-4 py-3">{{ $jejak->aksi }}</td>
                        <td class="px-4 py-3 text-tinta/70">{{ $jejak->objek }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-tinta/70">Belum ada aktivitas tercatat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $audit->links() }}</div>
@endsection
