<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Native;

use Illuminate\Encryption\Encrypter;
use RuntimeException;

final readonly class InstallationKey
{
    public function __construct(
        private string $path,
    ) {}

    /**
     * @throws RuntimeException
     */
    public function read(string $cipher): string
    {
        $handle = fopen($this->path, mode: 'c+');

        if ($handle === false) {
            throw new RuntimeException("Cannot open the installation key at {$this->path}.");
        }

        try {
            flock($handle, LOCK_EX);

            $key = (string) stream_get_contents($handle);

            if ($key === '') {
                $key = 'base64:' . base64_encode(Encrypter::generateKey($cipher));
                fwrite($handle, $key);
                fflush($handle);
                chmod($this->path, permissions: 0o600);
            }

            return $key;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
