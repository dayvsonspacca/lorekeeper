<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Persistence\Codec\Codecs;
use Lorekeeper\Persistence\Exception\UnknownTypeException;
use Psl\Option;
use Throwable;

final readonly class Links
{
    public function __construct(
        private Codecs $codecs,
    ) {}

    public function to(Subject $subject): string
    {
        $codec = $this->codecs->subjectFor($subject);

        return '/' . $codec->key . '/' . rawurlencode($codec->encode($subject));
    }

    /** @return Option\Option<Subject> */
    public function parse(string $path): Option\Option
    {
        $parts = explode('/', trim($path, characters: '/'), limit: 2);
        if (count($parts) !== 2 || Kinds::byKey($parts[0])->isNone()) {
            return Option\none();
        }

        try {
            return Option\some($this->codecs->subject($parts[0])->decode(rawurldecode($parts[1])));
        } catch (UnknownTypeException|Throwable) {
            return Option\none();
        }
    }
}
