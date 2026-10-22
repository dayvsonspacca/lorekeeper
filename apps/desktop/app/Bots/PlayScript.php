<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Scripts\FlowNode;
use Lorekeeper\Apps\Desktop\Scripts\FlowScript;
use Lorekeeper\Apps\Desktop\Scripts\InvalidFlowFile;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Script\Outcome;
use Lorekeeper\Client\Script\OutcomeStatus;
use Lorekeeper\Client\Script\Watch;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\LinkedCancellationToken;

final readonly class PlayScript
{
    public static function next(Account $account, Client $client, CancellationTokenInterface $stop): ?Outcome
    {
        $script = $account->refresh()->script;
        if ($script === null) {
            return null;
        }

        try {
            $flow = $script->flow();
        } catch (InvalidFlowFile $invalid) {
            $account->scriptEnded($script);
            $account->log("{$script->name} cannot play: " . implode('; ', $invalid->problems), LogLevel::Error);

            return null;
        }

        $account->log("Playing {$script->name}");
        $cancel = Watch::until(static fn(): bool => $account->fresh()?->script_id !== $script->id);

        try {
            $outcome = Outcome::of($client->run(
                new FlowScript($flow, entering: static function (FlowNode $node) use ($account): void {
                    $account->stepping($node->step->describe());
                    $account->log("→ {$node->step->describe()}");
                }),
                new LinkedCancellationToken($stop, $cancel->token()),
            ));
        } finally {
            $cancel->release();
        }

        $account->scriptEnded($script);
        $account->log(...match ($outcome->status) {
            OutcomeStatus::Done => ["{$script->name}: {$outcome->summary}", LogLevel::Success],
            OutcomeStatus::Cancelled => ["{$script->name}: stopped", LogLevel::Warning],
            OutcomeStatus::Failed, OutcomeStatus::Disconnected => [
                "{$script->name} failed: {$outcome->summary}",
                LogLevel::Error,
            ],
        });

        return $outcome;
    }
}
