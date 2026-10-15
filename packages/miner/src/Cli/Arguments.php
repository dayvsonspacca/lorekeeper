<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Cli;

final readonly class Arguments
{
    /** @param array<string, string> $options */
    public function __construct(
        public string $routine,
        private array $options,
    ) {}

    /** @param list<string> $argv */
    public static function parse(array $argv): self
    {
        $options = [];

        foreach (array_slice($argv, offset: 2) as $argument) {
            $match = [];
            if (preg_match('/^--(\w+)=(.*)$/', $argument, $match) !== 1) {
                continue;
            }

            $options[$match[1]] = $match[2];
        }

        return new self($argv[1] ?? '', $options);
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    public function get(string $name, string $default = ''): string
    {
        return $this->options[$name] ?? $default;
    }
}
