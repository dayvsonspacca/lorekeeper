<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots;

use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;

final readonly class StepLog
{
    public static function describe(Instruction $instruction, null|Event|TimedOut $answer): string
    {
        return match (true) {
            $instruction instanceof Send => '> ' . self::name($instruction->command) . self::answer($answer),
            $instruction instanceof Await => '…' . self::answer($answer),
            $instruction instanceof Pause => "… paused {$instruction->duration->getTotalSeconds()}s",
            default => '? ' . self::name($instruction),
        };
    }

    public static function name(object $object): string
    {
        $class = $object::class;
        $short = substr($class, (int) strrpos($class, needle: '\\') + 1);

        return (string) preg_replace('/(Command|Event)$/', replacement: '', subject: $short);
    }

    private static function answer(null|Event|TimedOut $answer): string
    {
        return match (true) {
            $answer === null => '',
            $answer instanceof TimedOut => " … timed out after {$answer->await->timeout->getTotalSeconds()}s",
            default => ' < ' . self::name($answer),
        };
    }
}
