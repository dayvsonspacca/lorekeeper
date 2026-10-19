<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Recorder;

use Closure;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Traffic;
use Lorekeeper\Client\TrafficDirection;
use Override;
use Psl\DateTime\SecondsStyle;
use Psl\DateTime\Timestamp;
use Psl\IO;
use Psl\Observer\ObserverInterface;
use Psl\Observer\SubjectInterface;
use RuntimeException;
use SensitiveParameter;

/** @implements ObserverInterface<Connection> */
final class Recording implements ObserverInterface
{
    private const int SHOWN = 160;

    private int $messages = 0;

    /** @var Closure(): Timestamp */
    private readonly Closure $clock;

    /**
     * @param non-empty-string $path
     * @param null|Closure(): Timestamp $clock
     */
    public function __construct(
        private readonly string $path,
        #[SensitiveParameter]
        private readonly string $secret,
        private readonly ?IO\WriteHandleInterface $screen = null,
        ?Closure $clock = null,
    ) {
        $this->clock = $clock ?? Timestamp::now(...);
    }

    /** @throws RuntimeException */
    #[Override]
    public function update(SubjectInterface $subject): void
    {
        $traffic = $subject->traffic();
        if ($traffic !== null) {
            $this->record($traffic, ($this->clock)());
        }
    }

    public function messages(): int
    {
        return $this->messages;
    }

    /** @throws RuntimeException */
    private function record(Traffic $traffic, Timestamp $at): void
    {
        $message = $this->secret === ''
            ? $traffic->message
            : str_replace(search: $this->secret, replace: '<token>', subject: $traffic->message);
        $line = json_encode(
            [
                'at' => $at->toRfc3339(SecondsStyle::Milliseconds, useZ: true),
                'way' => $traffic->direction === TrafficDirection::Sent ? 'sent' : 'received',
                'message' => $message,
            ],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        if (file_put_contents($this->path, $line . "\n", FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException("Could not write to {$this->path}.");
        }

        $this->messages++;
        $this->screen?->writeAll(sprintf(
            "%s %s %s\n",
            $at->format('HH:mm:ss.SSS'),
            $traffic->direction === TrafficDirection::Sent ? '>' : '<',
            mb_strimwidth($message, start: 0, width: self::SHOWN, trim_marker: '…'),
        ));
    }
}
