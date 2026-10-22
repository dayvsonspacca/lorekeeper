<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Scripts;

use Closure;
use Generator;
use Lorekeeper\Bot\Steps\Check;
use Lorekeeper\Bot\Steps\StepScript;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;
use Override;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

final class FlowScript extends StepScript
{
    private int $quiet = 0;

    /**
     * @param null|Closure(): Timestamp $clock
     * @param null|Closure(FlowNode): void $entering
     */
    public function __construct(
        private readonly Flow $flow,
        ?Closure $clock = null,
        private readonly ?Closure $entering = null,
    ) {
        parent::__construct($clock);
    }

    #[Override]
    public function title(): string
    {
        return $this->flow->name;
    }

    #[Override]
    protected function rules(): Generator
    {
        $node = $this->flow->first();
        do {
            if ($this->quiet > count($this->flow->nodes)) {
                yield new Pause(Duration::seconds(1));
            }

            $this->quiet++;
            if ($this->entering !== null) {
                ($this->entering)($node);
            }

            $playing = $this->play($node);
            yield from $this->relay($playing);
            $exit = $playing->getReturn();
            $next = $this->flow->next($node->id, $exit instanceof Failed ? FlowNode::FAILED : $exit);
            if ($exit instanceof Failed && $next === null) {
                return new Failed("{$node->step->describe()}: {$exit->reason}");
            }

            $last = $node;
            $node = $next;
        } while ($node !== null);

        return new Done("ended after: {$last->step->describe()}");
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, Failed|non-empty-string> */
    private function play(FlowNode $node): Generator
    {
        $step = $node->step;
        if ($step instanceof Check) {
            $asking = $step->holds($this);
            yield from $asking;

            return $asking->getReturn() ? FlowNode::YES : FlowNode::NO;
        }

        $attempt = $this->attempt($step);
        yield from $attempt;

        return $attempt->getReturn() ?? FlowNode::NEXT;
    }

    /**
     * @template T
     *
     * @param Generator<int, Instruction, null|Event|TimedOut, T> $playing
     *
     * @return Generator<int, Instruction, null|Event|TimedOut, void>
     */
    private function relay(Generator $playing): Generator
    {
        while ($playing->valid()) {
            $answer = yield $playing->current();
            $this->quiet = 0;
            $playing->send($answer);
        }
    }
}
