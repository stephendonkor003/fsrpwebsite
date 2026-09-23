<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RegistrationIndexRequest;
use App\Models\EventRegistration;
use App\Support\AdminRegistrationReport;
use App\Support\SeedSummitRegistrationPdf;
use App\Support\SeedSummitRegistrationSummary;
use Illuminate\Http\Response;

class RegistrationController extends Controller
{
    public function index(
        RegistrationIndexRequest $request,
        AdminRegistrationReport $report,
    ): Response {
        $filters = $request->registrationFilters();
        $dailyTrend = $report->dailyTrend($filters);

        return response()->view('admin.registrations.index', [
            'registrations' => $report->paginate($filters),
            'filters' => $filters,
            'filterOptions' => $report->filterOptions(),
            'metrics' => $report->metrics($filters),
            'dailyTrend' => $dailyTrend,
            'countryBreakdown' => $report->countryBreakdown($filters),
            'maximumDailyCount' => max(1, (int) collect($dailyTrend)->max('count')),
        ]);
    }

    public function show(
        EventRegistration $registration,
        SeedSummitRegistrationSummary $summary,
    ): Response {
        $documentLinks = [];

        if (is_string($registration->passport_photo_path) && $registration->passport_photo_path !== '') {
            $documentLinks[] = [
                'url' => route('admin.registrations.document', [$registration, 'photo']),
                'label' => 'Delegate profile photo',
                'filename' => $registration->passport_photo_original_name ?: 'profile-photo',
                'previewable' => true,
            ];
        }

        if (is_string($registration->passport_scan_path) && $registration->passport_scan_path !== '') {
            $documentLinks[] = [
                'url' => route('admin.registrations.document', [$registration, 'passport']),
                'label' => 'Passport scan',
                'filename' => $registration->passport_scan_original_name ?: 'passport-scan',
                'previewable' => false,
            ];
        }

        return response()->view('admin.registrations.show', [
            'registration' => $registration,
            'summarySections' => $summary->for($registration),
            'documentLinks' => $documentLinks,
        ]);
    }

    public function pdf(
        EventRegistration $registration,
        SeedSummitRegistrationPdf $pdf,
    ): Response {
        return response($pdf->render($registration), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="registration-'.$registration->public_id.'.pdf"',
        ]);
    }
}
