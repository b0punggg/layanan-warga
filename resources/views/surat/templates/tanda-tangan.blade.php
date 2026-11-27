@php
    $penandaTte = 'data:image/png;base64,'.base64_encode((string) file_get_contents(public_path('kop/penanda-tte.png')));
@endphp
<table class="blok-ttd">
    <tr>
        <td></td>
        <td class="ttd">
            <p>{{ config('surat.kalurahan') }}, {{ $tanggalSurat ?? now()->translatedFormat('d F Y') }}</p>
            <p>Lurah {{ config('surat.kalurahan') }}</p>
            <p class="penanda-tte"><img src="{{ $penandaTte }}" alt="" style="height: 74pt; width: 74pt;"></p>
            <p class="nama-pejabat">Drs. Anang Zamroni, M.S.I.</p>
        </td>
    </tr>
</table>
