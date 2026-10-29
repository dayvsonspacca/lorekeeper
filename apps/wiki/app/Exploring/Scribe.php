<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Exploring;

use Lorekeeper\Apps\Wiki\Facts\Archive;
use Lorekeeper\Apps\Wiki\Facts\Reading\MessageFacts;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\MessageParser;
use Lorekeeper\Client\Traffic;
use Lorekeeper\Client\TrafficDirection;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Observer\ObserverInterface;
use Psl\Observer\SubjectInterface;

/** @implements ObserverInterface<Connection> */
final class Scribe implements ObserverInterface
{
    private int $facts = 0;

    #[Override]
    public function update(SubjectInterface $subject): void
    {
        $traffic = $subject->traffic();
        if ($traffic !== null) {
            $this->hear($traffic);
        }
    }

    public function hear(Traffic $traffic): void
    {
        if ($traffic->direction !== TrafficDirection::Received) {
            return;
        }

        MessageParser::parse($traffic->message)->apply(function (Message $message): void {
            $facts = MessageFacts::of($message);
            Archive::record($facts);
            $this->facts += count($facts);
        });
    }

    public function facts(): int
    {
        return $this->facts;
    }
}
