<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests;

final class ExploreTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('AQW_PLAYER');
        putenv('AQW_PASSWORD');

        parent::tearDown();
    }

    public function testItAsksForAPlayerToPlayWith(): void
    {
        putenv('AQW_PLAYER');
        putenv('AQW_PASSWORD');

        $this
            ->artisan('wiki:explore', ['script' => __DIR__ . '/Fixtures/NotAScript.php'])
            ->expectsOutputToContain('Set AQW_PLAYER and AQW_PASSWORD to play.')
            ->assertFailed();
    }

    public function testItNeedsAFileThatReturnsAScript(): void
    {
        putenv('AQW_PLAYER=Hero');
        putenv('AQW_PASSWORD=secret');

        $this
            ->artisan('wiki:explore', ['script' => __DIR__ . '/Fixtures/NotAScript.php'])
            ->expectsOutputToContain('does not return a script')
            ->assertFailed();
        $this
            ->artisan('wiki:explore', ['script' => __DIR__ . '/Fixtures/Missing.php'])
            ->expectsOutputToContain('There is no script at')
            ->assertFailed();
    }
}
