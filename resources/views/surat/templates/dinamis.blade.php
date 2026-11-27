@include('surat.templates.gaya')
<div class="surat">
    @include('surat.templates.kop')
    <div class="naskah">{!! $badan !!}</div>
    @include('surat.templates.tanda-tangan')
</div>
