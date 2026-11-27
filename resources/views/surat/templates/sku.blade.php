@include('surat.templates.gaya')
<div class="surat">
    @include('surat.templates.kop')
    <h1>SURAT KETERANGAN USAHA</h1>
    <p class="nomor">Nomor : {{ $nomor }}</p>
    <p class="pembuka">Pemerintah Kalurahan {{ config('surat.kalurahan') }}, Kapanewon {{ config('surat.kapanewon') }}, Kabupaten {{ config('surat.kabupaten') }}, dengan ini menerangkan bahwa :</p>
    <table class="identitas">
        <tr><th>Nama Lengkap</th><td>{{ $contoh['nama'] }}</td></tr>
        <tr><th>Nomor Induk Kependudukan ( NIK )</th><td>{{ $contoh['nik'] }}</td></tr>
        <tr><th>Tempat Dan Tanggal Lahir</th><td>{{ $contoh['tempat_lahir'] }}, {{ $contoh['tanggal_lahir'] }}</td></tr>
        <tr><th>Jenis Kelamin</th><td>{{ $contoh['jenis_kelamin'] }}</td></tr>
        <tr><th>Warga Negara</th><td>{{ $contoh['warga_negara'] ?? 'Indonesia' }}</td></tr>
        <tr><th>Agama</th><td>{{ $contoh['agama'] ?? '—' }}</td></tr>
        <tr><th>Pekerjaan</th><td>{{ $contoh['pekerjaan'] }}</td></tr>
        <tr><th>Alamat</th><td>{{ $contoh['alamat'] }}</td></tr>
        <tr><th>Jenis Usaha</th><td>{{ $contoh['jenis_usaha'] }}</td></tr>
        <tr><th>Alamat Usaha</th><td>{{ $contoh['alamat_usaha'] }}</td></tr>
    </table>
    <p class="pembuka">Demikian surat keterangan ini kami buat, agar dapat dipergunakan sebagaimana mestinya.</p>
    @include('surat.templates.tanda-tangan')
</div>
