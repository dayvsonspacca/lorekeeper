<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests;

use Illuminate\Http\UploadedFile;
use Lorekeeper\Apps\Desktop\Models\Script;
use Lorekeeper\Apps\Desktop\Scripts\FlowFile;

final readonly class ScriptFiles
{
    public static function crypto(): Script
    {
        $script = Script::fromFlow(FlowFile::parse(self::fixture()));
        $script->save();

        return $script;
    }

    public static function fixture(): string
    {
        return (string) file_get_contents(__DIR__ . '/Fixtures/flows/crypto-token-daily.lorebot');
    }

    public static function upload(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('script.lorebot', $contents);
    }
}
