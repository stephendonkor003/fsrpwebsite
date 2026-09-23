<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RegistrationIndexRequest;
use App\Support\AdminRegistrationExport;
use App\Support\AdminRegistrationReport;
use App\Support\AdminRegistrationReportPdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationExportController extends Controller
{
    public function __invoke(
        RegistrationIndexRequest $request,
        string $format,
        AdminRegistrationReport $report,
        AdminRegistrationExport $export,
        AdminRegistrationReportPdf $pdf,
    ): Response|StreamedResponse {
        $filters = $request->registrationFilters();
        $filename = 'registrations-'.now()->format('Y-m-d-His');

        if ($format === 'pdf') {
            $contents = $pdf->render(
                $report->query($filters)->lazyById(250),
                'Delegate registration report',
                $report->describeFilters($filters),
                now(),
            );

            return response($contents, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
            ]);
        }

        abort_unless(in_array($format, ['csv', 'excel'], true), 404);

        return response()->streamDownload(
            function () use ($export, $filters, $format, $report): void {
                $stream = fopen('php://output', 'wb');

                abort_unless(is_resource($stream), 500);

                try {
                    $registrations = $report->query($filters)->lazyById(250);

                    if ($format === 'csv') {
                        $export->writeCsv($registrations, $stream);
                    } else {
                        $export->writeSpreadsheetXml($registrations, $stream, 'Registrations');
                    }
                } finally {
                    fclose($stream);
                }
            },
            $filename.($format === 'csv' ? '.csv' : '.xls'),
            ['Content-Type' => $format === 'csv'
                ? 'text/csv; charset=UTF-8'
                : 'application/vnd.ms-excel; charset=UTF-8'],
        );
    }
}
