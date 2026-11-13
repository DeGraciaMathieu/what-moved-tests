<?php

namespace Tests\Support;

/**
 * What a reviewer sees of a pull request: the summary of the comment, and what the map draws, read from its texts
 * and from the titles of its arrows rather than from shapes or coordinates.
 */
final class PullRequest
{
    public function __construct(
        public readonly string $summary,
        public readonly string $map,
        public readonly string $report,
    ) {}

    /**
     * @return list<string> the contexts on the map
     */
    public function contexts(): array
    {
        preg_match_all('#font-size="12.5" font-weight="600">([^<]+)<tspan#', $this->map, $matches);

        return array_map(fn (string $name): string => html_entity_decode($name), $matches[1]);
    }

    /**
     * @return array<string, array{dependencies: int, added: int}> "From → To" => what the arrow carries
     */
    public function arrows(): array
    {
        preg_match_all('#<title>(.+?) → (.+?): (\d+) dependencies, (\d+) added</title>#', $this->map, $matches, PREG_SET_ORDER);
        $arrows = [];

        foreach ($matches as [, $from, $to, $dependencies, $added]) {
            $arrows[html_entity_decode($from).' → '.html_entity_decode($to)] = ['dependencies' => (int) $dependencies, 'added' => (int) $added];
        }

        return $arrows;
    }

    /**
     * @return list<string> the counts written over the touched contexts, as "1 of 3 changed"
     */
    public function counts(): array
    {
        preg_match_all('#class="(?:hot|new)">([^<]+)</text>#', $this->map, $matches);

        return array_map(fn (string $count): string => html_entity_decode($count), $matches[1]);
    }

    /**
     * @return list<string> the contexts drawn as created by the pull request
     */
    public function newContexts(): array
    {
        preg_match_all('#font-size="12.5" font-weight="600">([^<]+)<tspan dx="6" class="new">new</tspan>#', $this->map, $matches);

        return $matches[1];
    }

    /**
     * @return list<string> the classes listed in the report
     */
    public function reportedClasses(): array
    {
        preg_match('#const DATA = (\{.*\});#', $this->report, $matches);

        return array_column(json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR)['nodes'], 'id');
    }
}
