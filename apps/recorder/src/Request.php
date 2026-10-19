<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Recorder;

use Psl\DateTime\Duration;
use SensitiveParameter;

final readonly class Request
{
    private const int DEFAULT_SECONDS = 60;

    /**
     * @param non-empty-string $player
     */
    public function __construct(
        public string $script,
        public string $player,
        #[SensitiveParameter]
        public string $password,
        public ?string $server,
        public Duration $length,
    ) {}

    /**
     * @param list<string> $arguments
     * @param array<string, string> $environment
     */
    public static function parse(array $arguments, #[SensitiveParameter] array $environment): ?self
    {
        $script = null;
        $seconds = self::DEFAULT_SECONDS;
        foreach ($arguments as $argument) {
            $option = [];
            if (preg_match('/^--for=(\d+)$/', $argument, $option) === 1) {
                $seconds = (int) $option[1];

                continue;
            }

            $script ??= $argument;
        }

        $player = $environment['AQW_PLAYER'] ?? '';
        $password = $environment['AQW_PASSWORD'] ?? '';
        if ($script === null || $player === '' || $password === '') {
            return null;
        }

        return new self(
            $script,
            $player,
            $password,
            ($environment['AQW_SERVER'] ?? '') === '' ? null : $environment['AQW_SERVER'],
            Duration::seconds(max(1, $seconds)),
        );
    }
}
