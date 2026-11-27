@include('surat.templates.gaya')
<div class="surat">
    @include('surat.templates.kop')
    <h1>SURAT PENGANTAR SKCK</h1>
    <p class="nomor">Nomor: {{ $nomor }}</p>
    <p>Yang bertanda tangan di bawah ini, Lurah {{ config('surat.kalurahan') }}, menerangkan bahwa:</p>
    <table class="identitas">
        <tr><th>Nama</th><td>{{ $contoh['nama'] }}</td></tr>
        <tr><th>NIK</th><td>{{ $contoh['nik'] }}</td></tr>
        <tr><th>Tempat, tanggal lahir</th><td>{{ $contoh['tempat_lahir'] }}, {{ $contoh['tanggal_lahir'] }}</td></tr>
        <tr><th>Jenis kelamin</th><td>{{ $contoh['jenis_kelamin'] }}</td></tr>
        <tr><th>Pekerjaan</th><td>{{ $contoh['pekerjaan'] }}</td></tr>
        <tr><th>Alamat</th><td>{{ $contoh['alamat'] }}</td></tr>
    </table>
    <p>Surat pengantar ini dibuat untuk keperluan {{ $contoh['keperluan'] ?? '—' }}.</p>
    @include('surat.templates.tanda-tangan')
</div>
