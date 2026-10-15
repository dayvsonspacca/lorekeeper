<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Persistence\Store\FactStore;
use Stringable;

final class Names
{
    /** @var array<string, array<string, string>> */
    private array $loaded = [];

    public function __construct(
        private readonly FactStore $store,
    ) {}

    public function of(Subject $subject): string
    {
        $id = (string) $subject;
        $nameClass = Kinds::of($subject)->map(static fn(Kind $kind): ?string => $kind->nameClass)->unwrapOr(null);
        if ($nameClass === null) {
            return $id;
        }

        return $this->names($nameClass)[$id] ?? self::fallback($subject, $id);
    }

    private static function fallback(Subject $subject, string $id): string
    {
        if (!$subject instanceof NpcId) {
            return "#{$id}";
        }

        return (string) preg_replace('/_APOP$/', replacement: '', subject: $subject->popup->value);
    }

    /**
     * @param class-string $nameClass
     *
     * @return array<string, string>
     */
    private function names(string $nameClass): array
    {
        if (!array_key_exists($nameClass, $this->loaded)) {
            $names = [];
            foreach ($this->store->withValue($nameClass) as $stored) {
                $value = $stored->fact->value;
                $names[(string) $stored->fact->subject] = $value instanceof Stringable ? (string) $value : '';
            }

            $this->loaded[$nameClass] = $names;
        }

        return $this->loaded[$nameClass];
    }
}
