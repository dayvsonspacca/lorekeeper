<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki\Tests;

use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Wiki\Links;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LinksTest extends TestCase
{
    #[Test]
    public function it_builds_and_parses_subject_urls(): void
    {
        $links = new Links(DefaultCodecs::create());

        static::assertSame('/class_name/Blaze%20Binder', $links->to(new ClassName('Blaze Binder')));
        static::assertEquals(new ClassName('Blaze Binder'), $links->parse('/class_name/Blaze%20Binder')->unwrap());
        static::assertEquals(new ItemId(447), $links->parse('/item/447')->unwrap());
    }

    #[Test]
    public function it_rejects_unknown_or_invalid_paths(): void
    {
        $links = new Links(DefaultCodecs::create());

        static::assertTrue($links->parse('/')->isNone());
        static::assertTrue($links->parse('/unknown/1')->isNone());
        static::assertTrue($links->parse('/item/abc')->isNone());
    }
}
