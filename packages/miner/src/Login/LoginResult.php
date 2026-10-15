<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Login;

use Lorekeeper\Miner\Exception\LoginFailedException;
use Lorekeeper\Miner\Server;
use Psl\Network\Address;
use Psl\Type;
use Psl\Type\Exception\CoercionException;
use SensitiveParameter;

final readonly class LoginResult
{
    /**
     * @param non-empty-string $token
     * @param list<Server> $servers
     */
    public function __construct(
        #[SensitiveParameter]
        public string $token,
        public array $servers,
    ) {}

    /** @throws LoginFailedException */
    public static function fromJson(#[SensitiveParameter] string $json): self
    {
        $type = Type\json_decoded(Type\shape([
            'login' => Type\optional(Type\shape([
                'sToken' => Type\non_empty_string(),
            ], allowUnknownFields: true)),
            'sMsg' => Type\optional(Type\string()),
            'servers' => Type\optional(Type\vec(Type\shape([
                'sName' => Type\non_empty_string(),
                'sIP' => Type\non_empty_string(),
                'iPort' => Type\int_range(min: 1, max: 65_535),
                'bOnline' => Type\int(),
                'bUpg' => Type\int(),
            ], allowUnknownFields: true))),
        ], allowUnknownFields: true));

        try {
            $response = $type->coerce($json);
        } catch (CoercionException $exception) {
            throw new LoginFailedException('Unexpected login response.', previous: $exception);
        }

        $login = $response['login'] ?? null;
        if ($login === null) {
            throw new LoginFailedException(strip_tags($response['sMsg'] ?? 'Login failed.'));
        }

        return new self($login['sToken'], array_map(
            static fn(array $server): Server => new Server(
                $server['sName'],
                Address::tcp($server['sIP'], $server['iPort']),
                $server['bOnline'] === 1,
                $server['bUpg'] === 1,
            ),
            $response['servers'] ?? [],
        ));
    }
}
