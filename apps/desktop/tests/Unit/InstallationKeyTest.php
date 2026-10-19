<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Lorekeeper\Apps\Desktop\Native\InstallationKey;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InstallationKeyTest extends TestCase
{
    private string $directory;

    #[\Override]
    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/lorekeeper-' . bin2hex(random_bytes(8));
        new Filesystem()->ensureDirectoryExists($this->directory);
    }

    #[\Override]
    protected function tearDown(): void
    {
        new Filesystem()->deleteDirectory($this->directory);
    }

    #[Test]
    public function it_creates_a_key_for_the_cipher_the_first_time(): void
    {
        $key = new InstallationKey("{$this->directory}/installation.key")->read('AES-256-CBC');

        static::assertStringStartsWith('base64:', $key);
        static::assertTrue(Encrypter::supported(base64_decode(substr($key, offset: 7), strict: true), 'AES-256-CBC'));
        static::assertSame(0o600, fileperms("{$this->directory}/installation.key") & 0o777);
    }

    #[Test]
    public function it_keeps_the_same_key_afterwards(): void
    {
        $first = new InstallationKey("{$this->directory}/installation.key")->read('AES-256-CBC');
        $second = new InstallationKey("{$this->directory}/installation.key")->read('AES-256-CBC');

        static::assertSame($first, $second);
    }

    #[Test]
    public function every_installation_gets_its_own_key(): void
    {
        static::assertNotSame(
            new InstallationKey("{$this->directory}/one.key")->read('AES-256-CBC'),
            new InstallationKey("{$this->directory}/other.key")->read('AES-256-CBC'),
        );
    }
}
