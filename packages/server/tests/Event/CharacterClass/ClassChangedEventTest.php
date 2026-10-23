<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\CharacterClass;

use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassCategory;
use Lorekeeper\Server\Event\CharacterClass\ClassChangedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
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

        static::assertSame(93_426, $event->player->value);
        static::assertSame('Blaze Binder', $event->className->value);
        static::assertSame(ClassCategory::Caster, $event->category);
        static::assertSame(302_500, $event->points->value);
        static::assertSame('Spawn 93426 is Blaze Binder (Caster, 302500 CP)', $event->summary());
        static::assertSame(
            'Blaze Binders control the power of fire and darkness. They are known to burn their enemies to a crisp and bind their souls into service',
            $event->description->unwrap()->value,
        );
    }

    #[Test]
    public function it_extracts_the_class_of_other_players_without_description(): void
    {
        $message = JsonMessage::from(
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"updateClass","uid":93474,"sClassName":"Shaman","sClassCat":"C3","iCP":2689}}}',
        )->unwrap();

        $event = ClassChangedEvent::from($message)->unwrap();

        static::assertSame('Shaman', $event->className->value);
        static::assertSame(2689, $event->points->value);
        static::assertTrue($event->description->isNone());
    }

    #[Test]
    public function it_reads_melee_categories(): void
    {
        $message = JsonMessage::from(
            '{"b":{"o":{"cmd":"updateClass","uid":1,"sClassName":"Glacial Berserker","sClassCat":"M4","iCP":0}}}',
        )->unwrap();

        static::assertSame(ClassCategory::Melee, ClassChangedEvent::from($message)->unwrap()->category);
    }

    #[Test]
    public function it_reads_the_hybrid_category(): void
    {
        $message = JsonMessage::from(
            '{"b":{"o":{"cmd":"updateClass","uid":1,"sClassName":"Twin Path","sClassCat":"S1","iCP":0}}}',
        )->unwrap();

        $event = ClassChangedEvent::from($message)->unwrap();

        static::assertSame(ClassCategory::Hybrid, $event->category);
        static::assertSame('Spawn 1 is Twin Path (Hybrid, 0 CP)', $event->summary());
    }

    #[Test]
    public function it_rejects_unknown_categories(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage("unknown class category 'X1'");

        ClassChangedEvent::from(
            JsonMessage::from(
                '{"b":{"o":{"cmd":"updateClass","uid":1,"sClassName":"Mystery","sClassCat":"X1","iCP":0}}}',
            )->unwrap(),
        );
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
            JsonMessage::from('{"b":{"o":{"cmd":"updateClass","uid":1,"sClassCat":"C1","iCP":0}}}')->unwrap(),
        );
    }

    #[Test]
    public function it_rejects_messages_without_class_points(): void
    {
        $this->expectException(MalformedEventException::class);

        ClassChangedEvent::from(
            JsonMessage::from(
                '{"b":{"o":{"cmd":"updateClass","uid":1,"sClassName":"Shaman","sClassCat":"C3"}}}',
            )->unwrap(),
        );
    }
}
