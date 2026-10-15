<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\CharacterClass;

use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\ClassDescription;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\CharacterClass\ManaRegeneration;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Protocol\Event\CharacterClass\ClassChangedEvent;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClassChangedEventTest extends TestCase
{
    #[Test]
    public function it_extracts_the_full_class_of_the_own_character(): void
    {
        $message = JsonMessage::from(
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"updateClass","uid":93426,"sClassName":"Blaze Binder","sClassCat":"C1","iCP":302500,"sDesc":"Blaze Binders control the power of fire and darkness. They are known to burn their enemies to a crisp and bind their souls into service","aMRM":["Blaze Binder gain mana from all hits landed in combat, and especially on crits.  The amount depends on damage relative to *their own* HP total."]}}}',
        )->unwrap();

        $event = ClassChangedEvent::from($message)->unwrap();
        $class = new ClassName('Blaze Binder');

        static::assertSame(93_426, $event->player->value);
        static::assertEquals($class, $event->className);
        static::assertEquals(
            [
                new Fact($class, ClassCategory::Caster),
                new Fact(
                    $class,
                    new ClassDescription(
                        'Blaze Binders control the power of fire and darkness. They are known to burn their enemies to a crisp and bind their souls into service',
                    ),
                ),
                new Fact(
                    $class,
                    new ManaRegeneration(
                        'Blaze Binder gain mana from all hits landed in combat, and especially on crits.  The amount depends on damage relative to *their own* HP total.',
                    ),
                ),
            ],
            $event->facts(),
        );
    }

    #[Test]
    public function it_extracts_the_category_of_other_players_classes(): void
    {
        $message = JsonMessage::from(
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"updateClass","uid":93474,"sClassName":"Shaman","sClassCat":"C3","iCP":2689}}}',
        )->unwrap();

        static::assertEquals(
            [new Fact(new ClassName('Shaman'), ClassCategory::Caster)],
            ClassChangedEvent::from($message)->unwrap()->facts(),
        );
    }

    #[Test]
    public function it_reads_melee_categories_and_skips_unknown_ones(): void
    {
        $melee = JsonMessage::from(
            '{"b":{"o":{"cmd":"updateClass","uid":1,"sClassName":"Glacial Berserker","sClassCat":"M4"}}}',
        )->unwrap();
        $unknown = JsonMessage::from(
            '{"b":{"o":{"cmd":"updateClass","uid":1,"sClassName":"Mystery","sClassCat":"X1"}}}',
        )->unwrap();

        static::assertEquals(
            [new Fact(new ClassName('Glacial Berserker'), ClassCategory::Melee)],
            ClassChangedEvent::from($melee)->unwrap()->facts(),
        );
        static::assertSame([], ClassChangedEvent::from($unknown)->unwrap()->facts());
    }

    #[Test]
    public function it_ignores_other_commands(): void
    {
        static::assertTrue(
            ClassChangedEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"equipItem"}}}')->unwrap())->isNone(),
        );
    }

    #[Test]
    public function it_rejects_messages_without_class_name(): void
    {
        $this->expectException(MalformedEventException::class);

        ClassChangedEvent::from(
            JsonMessage::from('{"b":{"o":{"cmd":"updateClass","uid":1,"sClassCat":"C1"}}}')->unwrap(),
        );
    }
}
