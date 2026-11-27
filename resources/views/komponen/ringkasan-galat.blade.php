@if ($errors->any())
    <div class="rounded-xl border border-tanah/40 bg-white px-4 py-3 text-sm text-tanah" role="alert">
        <p class="font-semibold">Ada isian yang perlu diperbaiki.</p>
        <ul class="mt-1 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    </div>
@endif
