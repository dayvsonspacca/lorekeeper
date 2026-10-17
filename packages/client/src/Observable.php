<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use Psl\Observer\ObserverInterface;
use Psl\Observer\SubjectInterface;

/** @require-implements SubjectInterface */
trait Observable
{
    /** @var list<ObserverInterface<SubjectInterface>> */
    private array $observers = [];

    /** @param ObserverInterface<SubjectInterface> $observer */
    public function subscribe(ObserverInterface $observer): void
    {
        if (in_array($observer, $this->observers, strict: true)) {
            return;
        }

        $this->observers[] = $observer;
    }

    /** @param ObserverInterface<SubjectInterface> $observer */
    public function unsubscribe(ObserverInterface $observer): void
    {
        $this->observers = array_values(array_filter(
            $this->observers,
            static fn(ObserverInterface $subscribed): bool => $subscribed !== $observer,
        ));
    }

    public function notify(): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }
}
