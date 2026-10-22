<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class FontsTest extends TestCase
{
    use RefreshDatabase;

    private const string STYLESHEET = 'fonts/ibm-plex/ibm-plex.css';

    #[Test]
    public function the_app_brings_its_own_fonts_and_asks_no_one_for_them(): void
    {
        $this
            ->get('/')
            ->assertOk()
            ->assertSee(self::STYLESHEET)
            ->assertDontSee('fonts.googleapis.com')
            ->assertDontSee('fonts.bunny.net');
    }

    #[Test]
    public function every_font_the_stylesheet_names_ships_with_the_app(): void
    {
        $stylesheet = (string) file_get_contents(public_path(self::STYLESHEET));
        preg_match_all("/url\\('([^']+)'\\)/", $stylesheet, $fonts);

        static::assertCount(10, $fonts[1]);
        foreach ($fonts[1] as $font) {
            static::assertFileExists(public_path("fonts/ibm-plex/{$font}"));
        }
    }
}
