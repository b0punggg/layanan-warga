@php
    $idGalat = 'galat-'.preg_replace('/[^A-Za-z0-9_-]+/', '-', $nama);
@endphp
@if ($errors->has($nama))
    <p id="{{ $idGalat }}" class="mt-1 text-sm font-medium text-tanah" role="alert">{{ $errors->first($nama) }}</p>
@endif
