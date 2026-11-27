@include('surat.templates.gaya')
@php
    $statusKartu = app(\App\Support\IsianSurat::class)->frasaStatusKartu($contoh['status_kartu'] ?? null);
@endphp
<div class="surat">
    @include('surat.templates.kop')
    <h1>SURAT KETERANGAN TIDAK MAMPU</h1>
    <p class="nomor">Nomor : {{ $nomor }}</p>
    <p class="pembuka">Saya, yang bertanda tangan dibawah ini :</p>
    <table class="identitas">
        <tr><th>Nama</th><td>Drs. Anang Zamroni, M.S.I.</td></tr>
        <tr><th>Jabatan</th><td>Lurah {{ config('surat.kalurahan') }}</td></tr>
    </table>
    <p class="pembuka">dengan ini menerangkan bahwa :</p>
    <table class="identitas">
        <tr><th>Nama Lengkap</th><td>{{ $contoh['nama'] }}</td></tr>
        <tr><th>NIK</th><td>{{ $contoh['nik'] }}</td></tr>
        <tr><th>Tempat dan tanggal lahir</th><td>{{ $contoh['tempat_lahir'] }}, {{ $contoh['tanggal_lahir'] }}</td></tr>
        <tr><th>Pekerjaan</th><td>{{ $contoh['pekerjaan'] }}</td></tr>
        <tr><th>Alamat</th><td>{{ $contoh['alamat'] }}</td></tr>
    </table>
    <p>bahwa saudara tersebut diatas adalah keluarga tidak mampu dan berpenghasilan rendah yang {{ $statusKartu }} dalam Kartu Keluarga Miskin atau Kartu Keluarga Rentan Miskin. Surat keterangan ini dibuat untuk {{ $contoh['keperluan'] ?? '—' }}:</p>
    <table class="identitas">
        <tr><th>Nama</th><td>{{ $contoh['nama_anak'] ?? '—' }}</td></tr>
        <tr><th>NIK</th><td>{{ $contoh['nik_anak'] ?? '—' }}</td></tr>
        <tr><th>Tempat, Tgl Lahir</th><td>{{ $contoh['ttl_anak'] ?? '—' }}</td></tr>
    </table>
    <p>Demikian surat keterangan ini diberikan kepada yang bersangkutan untuk dapat dipergunakan sebagaimana mestinya.</p>
    @include('surat.templates.tanda-tangan')
</div>
