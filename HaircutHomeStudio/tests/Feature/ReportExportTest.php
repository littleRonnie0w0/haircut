<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_hairdresser_can_download_csv_and_pdf_reports(): void
    {
        $this->seed();
        $peluquero = User::query()->where('rol', 'peluquero')->firstOrFail();

        $csv = $this->actingAs($peluquero)->get(route('peluquero.reportes.export', [
            'anio' => 2026,
            'mes' => 9,
            'formato' => 'csv',
        ]));
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', $csv->headers->get('content-type'));
        $this->assertStringContainsString('Cliente', $csv->streamedContent());

        $pdf = $this->actingAs($peluquero)->get(route('peluquero.reportes.export', [
            'anio' => 2026,
            'mes' => 9,
            'formato' => 'pdf',
        ]));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-1.4', $pdf->getContent());
    }
}
