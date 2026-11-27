<?php

namespace Tests\Unit;

use App\Services\NomorSurat;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class NomorSuratTest extends TestCase
{
    public function test_format_contoh_analisis(): void
    {
        $nomor = app(NomorSurat::class)->susun('SKU', 12, Carbon::parse('2026-11-18'));

        $this->assertSame('SKU/012/XI/2026', $nomor);
    }

    public function test_urut_dibaca_dari_nomor_yang_diedit(): void
    {
        $nomor = app(NomorSurat::class);

        $this->assertSame(151, $nomor->urutDariTeks('400.10.5/151', '400.10.5/{nomor}'));
        $this->assertSame(12, $nomor->urutDariTeks('SKU/012/XI/2026'));
    }

    public function test_nomor_urut_dimulai_dari_satu(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(NomorSurat::class)->susun('SKU', 0, Carbon::parse('2026-01-01'));
    }
}
