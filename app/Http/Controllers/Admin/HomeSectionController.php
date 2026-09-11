<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeSectionController extends Controller
{
    public function edit(): View
    {
        return view('admin.home-sections.index', [
            'sections' => HomeSection::orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sections' => ['required', 'array'],
            'sections.*.id' => ['required', 'integer', 'exists:home_sections,id'],
            'sections.*.sort_order' => ['required', 'integer', 'min:0', 'max:100'],
            'sections.*.is_active' => ['nullable', 'boolean'],
        ]);

        foreach ($validated['sections'] as $section) {
            HomeSection::whereKey($section['id'])->update([
                'sort_order' => $section['sort_order'],
                'is_active' => (bool) ($section['is_active'] ?? false),
            ]);
        }

        return back()->with('status', 'Homepage layout updated.');
    }
}
