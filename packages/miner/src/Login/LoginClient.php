<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Login;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Miner\Exception\LoginFailedException;
use SensitiveParameter;

final readonly class LoginClient
{
    private const string URL = 'https://game.aq.com/game/api/login/now';

    /** @throws LoginFailedException */
    public function login(PlayerName $player, #[SensitiveParameter] string $password): LoginResult
    {
        $curl = curl_init(self::URL);

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['user' => (string) $player, 'pass' => $password, 'option' => '1']),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);

        $response = curl_exec($curl);

        if (!is_string($response)) {
            throw new LoginFailedException('Could not reach the login API: ' . curl_error($curl));
        }

        return LoginResult::fromJson($response);
    }
}
