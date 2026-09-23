<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RegistrationIndexRequest;
use App\Models\Event;
use App\Models\Faq;
use App\Models\NewsPost;
use App\Models\Program;
use App\Models\Session;
use App\Models\Slide;
use App\Support\AdminRegistrationReport;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        RegistrationIndexRequest $request,
        AdminRegistrationReport $registrationReport,
    ): View {
        $registrationFilters = $request->registrationFilters('30_days');
        $registrationTrend = $registrationReport->dailyTrend($registrationFilters, 31);
        $stats = [
            ['label' => 'Published events', 'value' => Event::where('is_published', true)->count(), 'icon' => 'calendar'],
            ['label' => 'Agenda sessions', 'value' => Session::where('is_published', true)->count(), 'icon' => 'clock'],
            ['label' => 'News stories', 'value' => NewsPost::where('is_published', true)->count(), 'icon' => 'news'],
            ['label' => 'Programme tracks', 'value' => Program::where('is_published', true)->count(), 'icon' => 'programme'],
            ['label' => 'Active slides', 'value' => Slide::where('is_active', true)->count(), 'icon' => 'image'],
            ['label' => 'Published FAQs', 'value' => Faq::where('is_published', true)->count(), 'icon' => 'help'],
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'upcomingEvents' => Event::where('is_published', true)->where('start_at', '>=', now())->orderBy('start_at')->limit(5)->get(),
            'recentNews' => NewsPost::orderByDesc('published_at')->limit(5)->get(),
            'registrationMetrics' => $registrationReport->metrics($registrationFilters),
            'registrationTrend' => $registrationTrend,
            'registrationCountries' => $registrationReport->countryBreakdown($registrationFilters, 10),
            'recentRegistrations' => $registrationReport->recent($registrationFilters),
            'registrationFilters' => $registrationFilters,
            'registrationFilterOptions' => $registrationReport->filterOptions(),
            'maximumRegistrationDayCount' => max(1, (int) collect($registrationTrend)->max('count')),
        ]);
    }
}
