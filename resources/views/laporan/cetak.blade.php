<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    <h1>Rekap pengajuan {{ $tahun }}@if ($bulan) bulan {{ $bulan }}@endif</h1>
    <p>Kalurahan {{ config('surat.kalurahan') }}</p>
    <h2>Per jenis surat</h2>
    <table>
        <tr><th>Jenis</th><th>Diajukan</th><th>Selesai</th></tr>
        @forelse ($per_jenis as $nama => $angka)
            <tr><td>{{ $nama }}</td><td>{{ $angka['jumlah'] }}</td><td>{{ $angka['selesai'] }}</td></tr>
        @empty
            <tr><td colspan="3">Belum ada pengajuan.</td></tr>
        @endforelse
    </table>
    <h2>Per dukuh</h2>
    <table>
        <tr><th>Dukuh</th><th>Diajukan</th></tr>
        @forelse ($per_dukuh as $dukuh => $jumlah)
            <tr><td>{{ $dukuh }}</td><td>{{ $jumlah }}</td></tr>
        @empty
            <tr><td colspan="2">Belum ada pengajuan.</td></tr>
        @endforelse
    </table>
</body>
</html>
