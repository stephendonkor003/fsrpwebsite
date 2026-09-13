<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpeakersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_speakers_page_displays_profiles_and_navigation_link(): void
    {
        $this->get('/en/speakers')
            ->assertOk()
            ->assertSee('H.E. Moses Vilakati')
            ->assertSee('Nardos Bekele-Thomas')
            ->assertSee('Dr. Anxious Jongwe Masuka')
            ->assertSee('Elias Mpedi Magosi')
            ->assertSee('Gabriel Mbairobe')
            ->assertSee('data-speaker-open', false)
            ->assertSee('class="speaker-modal"', false)
            ->assertSee(route('speakers', 'en'), false);
    }
}
