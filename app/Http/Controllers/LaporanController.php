<?php

namespace App\Http\Controllers;

use App\Services\RekapLayanan;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    public function index(Request $request, RekapLayanan $rekap): View
    {
        $susun = $this->susun($request, $rekap);

        return view('laporan.index', $susun);
    }

    public function excel(Request $request, RekapLayanan $rekap): StreamedResponse
    {
        $susun = $this->susun($request, $rekap);
        $buku = new Spreadsheet;
        $lembar = $buku->getActiveSheet();
        $lembar->setTitle('Rekap');
        $baris = 1;
        $lembar->setCellValue('A'.$baris, 'Jenis surat');
        $lembar->setCellValue('B'.$baris, 'Diajukan');
        $lembar->setCellValue('C'.$baris, 'Selesai');

        foreach ($susun['per_jenis'] as $nama => $angka) {
            $baris++;
            $lembar->setCellValue('A'.$baris, $nama);
            $lembar->setCellValue('B'.$baris, $angka['jumlah']);
            $lembar->setCellValue('C'.$baris, $angka['selesai']);
        }

        $baris += 2;
        $lembar->setCellValue('A'.$baris, 'Dukuh');
        $lembar->setCellValue('B'.$baris, 'Diajukan');

        foreach ($susun['per_dukuh'] as $dukuh => $jumlah) {
            $baris++;
            $lembar->setCellValue('A'.$baris, $dukuh);
            $lembar->setCellValue('B'.$baris, $jumlah);
        }

        $penulis = new Xlsx($buku);

        return response()->streamDownload(function () use ($penulis): void {
            $penulis->save('php://output');
        }, 'rekap-layanan-'.$susun['tahun'].'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function pdf(Request $request, RekapLayanan $rekap): Response
    {
        $susun = $this->susun($request, $rekap);
        $html = view('laporan.cetak', $susun)->render();
        $opsi = new Options;
        $opsi->set('isRemoteEnabled', false);
        $opsi->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($opsi);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="rekap-layanan.pdf"',
        ]);
    }

    /**
     * @return array{tahun: int, bulan: ?int, per_jenis: Collection, per_dukuh: Collection}
     */
    private function susun(Request $request, RekapLayanan $rekap): array
    {
        $data = $request->validate([
            'tahun' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'bulan' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        return $rekap->susun(
            (int) ($data['tahun'] ?? now()->year),
            isset($data['bulan']) ? (int) $data['bulan'] : null,
        );
    }
}
