<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Quest\Requirement;

use Lorekeeper\Core\CharacterClass\ClassId;
use Lorekeeper\Core\Progression\Rank;
use Lorekeeper\Core\World\Quest\Requirement\ClassRankRequirement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClassRankRequirementTest extends TestCase
{
    #[Test]
    public function it_holds_class_and_rank(): void
    {
        $requirement = new ClassRankRequirement(new ClassId(2), new Rank(4));

        static::assertSame(2, $requirement->classId->value);
        static::assertSame(4, $requirement->rank->value);
    }
}
