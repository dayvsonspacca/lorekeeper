<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Api\Server;

use Lorekeeper\Server\Server;
use Psl\Network\Address;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class ServerListClient
{
    /** @param non-empty-string $url */
    public function __construct(
        private string $url = 'https://game.aq.com/game/api/data/servers',
    ) {}

    /**
     * @throws ServerListUnavailableException
     *
     * @return list<Server>
     */
    public function fetch(): array
    {
        $curl = curl_init($this->url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);

        $response = curl_exec($curl);
        if (!is_string($response)) {
            throw new ServerListUnavailableException('Could not reach the server list API: ' . curl_error($curl));
        }

        try {
            $servers = Type\json_decoded(Type\vec(Type\shape([
                'sName' => Type\non_empty_string(),
                'sIP' => Type\non_empty_string(),
                'iPort' => Type\int_range(min: 1, max: 65_535),
                'bOnline' => Type\int(),
                'bUpg' => Type\int(),
            ], allowUnknownFields: true)))
                ->coerce($response);
        } catch (CoercionException $exception) {
            throw new ServerListUnavailableException('Unexpected server list response.', previous: $exception);
        }

        return array_map(
            static fn(array $server): Server => new Server(
                $server['sName'],
                Address::tcp($server['sIP'], $server['iPort']),
                $server['bOnline'] === 1,
                $server['bUpg'] === 1,
            ),
            $servers,
        );
    }
}
