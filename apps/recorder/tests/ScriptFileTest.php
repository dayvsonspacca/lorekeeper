<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Recorder\Tests;

use Lorekeeper\Apps\Recorder\ScriptFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ScriptFileTest extends TestCase
{
    #[Test]
    public function it_plays_whatever_script_the_file_returns(): void
    {
        static::assertSame('Nothing to do', ScriptFile::load(__DIR__ . '/Fixtures/DoNothing.php')->title());
        static::assertSame('DoNothing', ScriptFile::name(__DIR__ . '/Fixtures/DoNothing.php'));
    }

    #[Test]
    public function it_refuses_a_file_that_returns_no_script(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not return a script');

        ScriptFile::load(__DIR__ . '/Fixtures/NotAScript.php');
    }

    #[Test]
    public function it_refuses_a_file_that_is_not_there(): void
    {
        $this->expectException(RuntimeException::class);

        ScriptFile::load(__DIR__ . '/Fixtures/missing.php');
    }
}
