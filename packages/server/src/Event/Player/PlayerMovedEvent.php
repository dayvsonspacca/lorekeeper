<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\Event\Map\Helper\MapPads;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * A player in the room went to another cell of the map.
 */
final readonly class PlayerMovedEvent extends PlayerUpdatedEvent
{
    public function __construct(
        PlayerName $name,
        public MapSpot $spot,
    ) {
        parent::__construct($name);
    }

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        return match (true) {
            $message instanceof DelimitedMessage && ($message->data[1] ?? null) === 'uotls' => self::delimited(
                $message,
            ),
            $message instanceof JsonMessage && ($message->data['cmd'] ?? null) === 'uotls' => self::json($message),
            default => Option\none(),
        };
    }

    #[Override]
    public function summary(): string
    {
        return "{$this->name->value} moved to {$this->spot->cell->value} ({$this->spot->pad->name})";
    }

    /**
     * @throws MalformedEventException
     *
     * @return Option\Option<static>
     */
    private static function delimited(DelimitedMessage $message): Option\Option
    {
        $update = $message->data[4] ?? '';
        $frame = [];
        $side = [];
        $cell = preg_match('/(?:^|,)strFrame:([^,]*)/', $update, $frame) === 1 ? $frame[1] : null;
        $pad = preg_match('/(?:^|,)strPad:([^,]*)/', $update, $side) === 1 ? $side[1] : null;

        return self::moved($message->data[3] ?? null, $cell, $pad);
    }

    /**
     * @throws MalformedEventException
     *
     * @return Option\Option<static>
     */
    private static function json(JsonMessage $message): Option\Option
    {
        try {
            $update = Type\shape([
                'unm' => Type\optional(Type\mixed()),
                'o' => Type\optional(Type\shape([
                    'strFrame' => Type\optional(Type\mixed()),
                    'strPad' => Type\optional(Type\mixed()),
                ], allowUnknownFields: true)),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'player update is invalid', $exception);
        }

        return self::moved($update['unm'] ?? null, $update['o']['strFrame'] ?? null, $update['o']['strPad'] ?? null);
    }

    /**
     * @throws MalformedEventException
     *
     * @return Option\Option<static>
     */
    private static function moved(mixed $name, mixed $cell, mixed $pad): Option\Option
    {
        if ($pad === null) {
            return Option\none();
        }

        try {
            $name = Type\non_empty_string()->coerce($name);
            $cell = Type\non_empty_string()->coerce($cell);
            $pad = Type\non_empty_string()->coerce($pad);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'player name, cell or pad is missing', $exception);
        }

        return Option\some(
            new static(new PlayerName($name), new MapSpot(new MapCell($cell), MapPads::read($pad, static::class))),
        );
    }
}
