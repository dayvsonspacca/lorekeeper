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
     * @param array<string, string> $options
     *
     * @mago-expect lint:excessive-parameter-list
     */
    public function __construct(
        public Scenario $scenario,
        public string $player,
        #[SensitiveParameter]
        public string $password,
        public ?string $server,
        public Duration $length,
        public array $options,
    ) {}

    /**
     * @param list<string> $arguments
     * @param array<string, string> $environment
     */
    public static function parse(array $arguments, #[SensitiveParameter] array $environment): ?self
    {
        $scenario = null;
        $options = [];
        foreach ($arguments as $argument) {
            $option = [];
            if (preg_match('/^--([a-z]+)=(.+)$/', $argument, $option) === 1) {
                $options[$option[1]] = $option[2];

                continue;
            }

            $scenario ??= Scenario::tryFrom($argument);
        }

        $player = $environment['AQW_PLAYER'] ?? '';
        $password = $environment['AQW_PASSWORD'] ?? '';
        if ($scenario === null || $player === '' || $password === '') {
            return null;
        }

        $seconds = (int) ($options['for'] ?? self::DEFAULT_SECONDS);

        return new self(
            $scenario,
            $player,
            $password,
            ($environment['AQW_SERVER'] ?? '') === '' ? null : $environment['AQW_SERVER'],
            Duration::seconds(max(1, $seconds)),
            $options,
        );
    }
}
