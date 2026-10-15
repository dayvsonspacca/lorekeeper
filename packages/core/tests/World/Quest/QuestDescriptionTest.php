<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest;

use Lorekeeper\Core\World\Quest\QuestDescription;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class QuestDescriptionTest extends TestCase
{
    #[Test]
    public function it_holds_description_and_end_text(): void
    {
        $quest = new QuestDescription('Defeat 10 skeletons.', 'Thank you, hero!');

        static::assertSame('Defeat 10 skeletons.', $quest->description);
        static::assertSame('Thank you, hero!', $quest->endText->unwrap());
    }

    #[Test]
    public function it_has_no_end_text_when_empty(): void
    {
        $quest = new QuestDescription('Defeat 10 skeletons.');

        static::assertTrue($quest->endText->isNone());
    }

    #[Test]
    public function it_rejects_an_empty_description(): void
    {
        $this->expectException(AssertException::class);

        new QuestDescription('');
    }
}
