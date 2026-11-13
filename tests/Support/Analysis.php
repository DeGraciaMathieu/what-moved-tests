<?php

namespace Tests\Support;

/**
 * What graph.php found in a project: its classes, the context of each, and the dependencies between them.
 */
final class Analysis
{
    /**
     * @param  array{nodes: list<array{id: string, context: string}>, edges: list<array{0: string, 1: string}>}  $graph
     */
    public function __construct(private readonly array $graph) {}

    /**
     * @return list<string>
     */
    public function classes(): array
    {
        return array_column($this->graph['nodes'], 'id');
    }

    /**
     * @return array<string, string> class => context
     */
    public function contexts(): array
    {
        return array_column($this->graph['nodes'], 'context', 'id');
    }

    /**
     * @return list<string> the classes the given class uses
     */
    public function dependenciesOf(string $class): array
    {
        return array_values(array_map(
            fn (array $edge): string => $edge[1],
            array_filter($this->graph['edges'], fn (array $edge): bool => $edge[0] === $class),
        ));
    }
}
