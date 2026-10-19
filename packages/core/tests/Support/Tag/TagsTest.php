<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Support\Tag;

use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Support\Tag\Tags;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TagsTest extends TestCase
{
    #[Test]
    public function it_has_only_the_tags_added_to_it(): void
    {
        /** @var Tags<ItemTag> $tags */
        $tags = new Tags();

        static::assertFalse($tags->has(ItemTag::MemberOnly));
        static::assertTrue($tags->add(ItemTag::MemberOnly)->add(ItemTag::MemberOnly)->has(ItemTag::MemberOnly));
    }
}
