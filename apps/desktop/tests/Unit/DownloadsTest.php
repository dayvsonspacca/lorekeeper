<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Lorekeeper\Apps\Desktop\Native\Downloads;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

final class DownloadsTest extends TestCase
{
    private string $home;

    private string $was;

    private string $config;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->was = (string) getenv('HOME');
        $this->config = (string) getenv('XDG_CONFIG_HOME');
        $this->home = sys_get_temp_dir() . '/lorekeeper-home-' . bin2hex(random_bytes(4));
        mkdir($this->home);
        putenv("HOME={$this->home}");
        putenv('XDG_CONFIG_HOME');
        config(['desktop.downloads' => null]);
    }

    #[Override]
    protected function tearDown(): void
    {
        putenv("HOME={$this->was}");
        putenv($this->config === '' ? 'XDG_CONFIG_HOME' : "XDG_CONFIG_HOME={$this->config}");
        exec('rm -r ' . escapeshellarg($this->home));

        parent::tearDown();
    }

    #[Test]
    public function it_saves_in_the_folder_the_desktop_names_for_downloads(): void
    {
        mkdir("{$this->home}/.config");
        mkdir("{$this->home}/Transferências");
        file_put_contents(
            "{$this->home}/.config/user-dirs.dirs",
            data: "XDG_DESKTOP_DIR=\"\$HOME/Desktop\"\nXDG_DOWNLOAD_DIR=\"\$HOME/Transferências\"\n",
        );

        static::assertSame("{$this->home}/Transferências", Downloads::folder());
    }

    #[Test]
    public function without_a_named_folder_it_uses_downloads_and_then_home(): void
    {
        static::assertSame($this->home, Downloads::folder());

        mkdir("{$this->home}/Downloads");
        static::assertSame("{$this->home}/Downloads", Downloads::folder());
    }

    #[Test]
    public function a_title_with_no_letters_is_saved_as_script(): void
    {
        static::assertSame("{$this->home}/script.lorebot", Downloads::save('!!!', '{}', extension: 'lorebot'));
        static::assertSame('{}', file_get_contents("{$this->home}/script.lorebot"));
    }
}
