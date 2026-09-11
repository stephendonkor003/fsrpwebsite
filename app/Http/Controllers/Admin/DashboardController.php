<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Faq;
use App\Models\NewsPost;
use App\Models\Program;
use App\Models\Session;
use App\Models\Slide;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
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
        ]);
    }
}
