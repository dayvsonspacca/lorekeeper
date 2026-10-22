<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Scripts;

use Closure;
use InvalidArgumentException;
use Psl\DateTime\Timestamp;

final readonly class Flow
{
    /** @var array<non-empty-string, FlowNode> */
    private array $byId;

    /**
     * @mago-expect lint:excessive-parameter-list
     *
     * @param non-empty-string $name
     * @param null|non-empty-string $author
     * @param non-empty-string $start
     * @param non-empty-list<FlowNode> $nodes
     * @param list<Wire> $wires
     * @param null|non-empty-string $group
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public string $name,
        public ?string $author,
        public string $start,
        public array $nodes,
        public array $wires = [],
        public ?string $group = null,
    ) {
        $byId = [];
        foreach ($nodes as $node) {
            $byId[$node->id] = $node;
        }

        if (!array_key_exists($start, $byId)) {
            throw new InvalidArgumentException("The flow starts at '{$start}', which is not one of its nodes.");
        }

        $this->byId = $byId;
    }

    public function first(): FlowNode
    {
        return $this->byId[$this->start];
    }

    public function node(string $id): ?FlowNode
    {
        return $this->byId[$id] ?? null;
    }

    public function next(string $from, string $exit): ?FlowNode
    {
        foreach ($this->wires as $wire) {
            if ($wire->from === $from && $wire->exit === $exit) {
                return $this->node($wire->to);
            }
        }

        return null;
    }

    /**
     * @param null|Closure(): Timestamp $clock
     * @param null|Closure(FlowNode): void $entering
     */
    public function script(?Closure $clock = null, ?Closure $entering = null): FlowScript
    {
        return new FlowScript($this, $clock, $entering);
    }
}
