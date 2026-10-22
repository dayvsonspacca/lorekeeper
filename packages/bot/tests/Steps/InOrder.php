<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Steps;

use Closure;
use Generator;
use Lorekeeper\Bot\Steps\Action;
use Lorekeeper\Bot\Steps\Check;
use Lorekeeper\Bot\Steps\StepScript;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Failed;
use Override;
use Psl\DateTime\Timestamp;

final class InOrder extends StepScript
{
    /**
     * @param non-empty-list<Action|Check> $steps
     * @param null|Closure(): Timestamp $clock
     */
    public function __construct(
        private readonly array $steps,
        ?Closure $clock = null,
    ) {
        parent::__construct($clock);
    }

    #[Override]
    public function title(): string
    {
        return 'In order';
    }

    #[Override]
    protected function rules(): Generator
    {
        foreach ($this->steps as $index => $step) {
            if ($step instanceof Check) {
                $asking = $step->holds($this);
                yield from $asking;
                if ($asking->getReturn()) {
                    return new Done("stopped: {$step->describe()}");
                }

                continue;
            }

            $attempt = $this->attempt($step);
            yield from $attempt;
            $failed = $attempt->getReturn();
            if ($failed !== null) {
                return new Failed('step ' . ($index + 1) . " ({$step->describe()}): {$failed->reason}");
            }
        }

        return new Done('all steps are done');
    }
}
