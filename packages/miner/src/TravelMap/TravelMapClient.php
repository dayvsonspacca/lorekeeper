<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\TravelMap;

use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Miner\Exception\TravelMapUnavailableException;

final readonly class TravelMapClient
{
    private const string URL = 'https://game.aq.com/game/api/data/travelmap';

    /**
     * @throws TravelMapUnavailableException
     *
     * @return list<Fact<Subject, object>>
     */
    public function fetch(): array
    {
        $curl = curl_init(self::URL);

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

        return TravelMapFacts::fromJson($response);
    }
}
