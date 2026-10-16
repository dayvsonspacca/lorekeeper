<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Message;

use DOMDocument;
use Override;
use Psl\Option;

final readonly class XmlMessage implements Message
{
    public function __construct(
        public DOMDocument $dom,
        public string $raw,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(string $message): Option\Option
    {
        if ($message === '') {
            return Option\none();
        }

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($message);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false) {
            return Option\none();
        }

        return Option\some(new self($dom, $message));
    }
}
