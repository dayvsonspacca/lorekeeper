<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Combat;

use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\Player\PlayerSkill;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SkillsLoadedEventTest extends TestCase
{
    #[Test]
    public function it_leaves_out_an_action_in_a_slot_it_does_not_know(): void
    {
        $event = SkillsLoadedEvent::from(JsonMessage::from(<<<'JSON'
            {"t":"xt","b":{"r":-1,"o":{"cmd":"sAct","actions":{"active":[{"id":89,"nam":"Auto Attack","typ":"aa","ref":"aa","tgt":"h","fx":"m","damage":0.9,"range":301,"mp":0,"cd":2000,"auto":true,"isOK":true},{"id":115,"nam":"Fireball","typ":"m","ref":"a5","tgt":"h","tgtMax":1,"mp":20,"cd":6000,"isOK":true}]}}}}
            JSON)->unwrap())->unwrap();

        static::assertSame(
            [SkillSlot::AutoAttack],
            array_map(static fn(PlayerSkill $held): SkillSlot => $held->skill->slot, $event->skills),
        );
    }
}
