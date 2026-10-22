<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Lorekeeper\Core\Player\GameSetting;
use Override;

/**
 * @property int $id
 * @property bool $block_goto
 * @property bool $block_whispers
 * @property bool $block_friend_requests
 * @property bool $block_party_invites
 * @property bool $block_guild_invites
 * @property bool $block_duels
 * @property bool $private_rooms
 * @property bool $play_together
 * @property bool $start_with_computer
 * @property bool $notify_updates
 */
#[Fillable([
    'block_goto',
    'block_whispers',
    'block_friend_requests',
    'block_party_invites',
    'block_guild_invites',
    'block_duels',
    'private_rooms',
    'play_together',
    'start_with_computer',
    'notify_updates',
])]
final class Preferences extends Model
{
    public const array BLOCKS = [
        'block_goto' => GameSetting::Goto,
        'block_whispers' => GameSetting::Whispers,
        'block_friend_requests' => GameSetting::FriendRequests,
        'block_party_invites' => GameSetting::PartyInvites,
        'block_guild_invites' => GameSetting::GuildInvites,
        'block_duels' => GameSetting::Duels,
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'block_goto' => true,
        'block_whispers' => true,
        'block_friend_requests' => true,
        'block_party_invites' => true,
        'block_guild_invites' => true,
        'block_duels' => true,
        'private_rooms' => true,
        'play_together' => true,
        'start_with_computer' => false,
        'notify_updates' => true,
    ];

    public static function current(): self
    {
        $found = self::query()->oldest('id')->first();

        return $found instanceof self ? $found : self::query()->create();
    }

    public function blocks(GameSetting $setting): bool
    {
        $column = array_search($setting, self::BLOCKS, strict: true);

        return $column !== false && (bool) $this->getAttribute($column);
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return array_fill_keys([
            'private_rooms',
            'play_together',
            'start_with_computer',
            'notify_updates',
            ...array_keys(self::BLOCKS),
        ], value: 'boolean');
    }
}
