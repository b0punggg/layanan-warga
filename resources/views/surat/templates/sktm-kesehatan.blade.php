@include('surat.templates.gaya')
@php
    $nilaiKartu = $contoh['status_kartu_kesehatan'] ?? null;
    if ($nilaiKartu === null || $nilaiKartu === '' || $nilaiKartu === '—') {
        $nilaiKartu = $contoh['status_kartu'] ?? null;
    }
    $statusKartu = app(\App\Support\IsianSurat::class)->frasaTermasukKartu($nilaiKartu);
    $keterangan = 'Menerangkan orang tersebut diatas termasuk dalam keluarga tidak mampu dan berpenghasilan rendah yang '.$statusKartu.' dalam Kartu Keluarga Miskin dan Kartu Keluarga Rentan Miskin.';
@endphp
<div class="surat">
    @include('surat.templates.kop')
    <h1>SURAT KETERANGAN TIDAK MAMPU</h1>
    <p class="nomor">Nomor : {{ $nomor }}</p>
    <p class="pembuka">Pemerintah Kalurahan {{ config('surat.kalurahan') }}, Kapanewon {{ config('surat.kapanewon') }}, Kabupaten {{ config('surat.kabupaten') }}, dengan ini menerangkan bahwa :</p>
    <table class="identitas">
        <tr><th>Nama lengkap</th><td>{{ $contoh['nama'] }}</td></tr>
        <tr><th>Nomor Induk Kependudukan ( NIK )</th><td>{{ $contoh['nik'] }}</td></tr>
        <tr><th>Tempat dan tanggal lahir</th><td>{{ $contoh['tempat_lahir'] }}, {{ $contoh['tanggal_lahir'] }}</td></tr>
        <tr><th>Jenis kelamin</th><td>{{ $contoh['jenis_kelamin'] }}</td></tr>
        <tr><th>Warga negara</th><td>{{ $contoh['warga_negara'] ?? 'Indonesia' }}</td></tr>
        <tr><th>Agama</th><td>{{ $contoh['agama'] ?? '—' }}</td></tr>
        <tr><th>Pekerjaan</th><td>{{ $contoh['pekerjaan'] }}</td></tr>
        <tr><th>Alamat</th><td>{{ $contoh['alamat'] }}</td></tr>
        <tr><th>Tujuan</th><td>{{ $contoh['tujuan'] ?? '—' }}</td></tr>
        <tr><th>Keperluan</th><td>{{ $contoh['keperluan'] ?? '—' }}</td></tr>
        <tr><th>Keterangan</th><td>{{ $keterangan }}</td></tr>
    </table>
    <p>Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
    @include('surat.templates.tanda-tangan')
</div>
