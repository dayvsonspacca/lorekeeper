<?php

declare(strict_types=1);

namespace Lorekeeper\Client\TravelMap;

use Lorekeeper\Client\Exception\TravelMapUnavailableException;

final readonly class TravelMapClient
{
    /** @param non-empty-string $url */
    public function __construct(
        private string $url = 'https://game.aq.com/game/api/data/travelmap',
    ) {}

    /** @throws TravelMapUnavailableException */
    public function fetch(): TravelMap
    {
        $curl = curl_init($this->url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);

        $response = curl_exec($curl);
        if (!is_string($response)) {
            throw new TravelMapUnavailableException('Could not reach the travel map API: ' . curl_error($curl));
        }

        return TravelMap::fromJson($response);
    }
}
