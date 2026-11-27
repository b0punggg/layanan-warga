@php
    $lambang = 'data:image/png;base64,'.base64_encode((string) file_get_contents(public_path('kop/lambang-sleman.png')));
    $hiasan = 'data:image/png;base64,'.base64_encode((string) file_get_contents(public_path('kop/hiasan-kop.png')));
@endphp
<header class="kop">
    <table class="kop-tabel">
        <tr>
            <td class="lambang"><img src="{{ $lambang }}" alt="" style="height: 72pt; width: 66pt;"></td>
            <td class="kop-teks">
                <p class="instansi">PEMERINTAH KABUPATEN {{ \Illuminate\Support\Str::upper(config('surat.kabupaten')) }}</p>
                <p class="instansi">KAPANEWON {{ \Illuminate\Support\Str::upper(config('surat.kapanewon')) }}</p>
                <p class="kalurahan">PEMERINTAH KALURAHAN {{ \Illuminate\Support\Str::upper(config('surat.kalurahan')) }}</p>
                <p class="hiasan"><img src="{{ $hiasan }}" alt="" style="height: 22pt; width: 250pt;"></p>
            </td>
        </tr>
    </table>
    <p class="alamat">Alamat: {{ config('surat.alamat_kalurahan') }}</p>
    <p class="alamat">Laman: {{ config('surat.laman') }}, Surel: <span class="surel">{{ config('surat.surel') }}</span></p>
    <hr class="garis-bawah">
</header>
