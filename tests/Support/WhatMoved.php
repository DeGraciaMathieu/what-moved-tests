<?php

namespace Tests\Support;

use RuntimeException;

/**
 * Runs the scripts of what-moved from the outside, as action.yml does: graph.php on a project, report.php on two graphs.
 * WHAT_MOVED_PATH points to a checkout of what-moved with its dependencies installed, next to this repository by default.
 */
final class WhatMoved
{
    public static function path(): string
    {
        return rtrim(getenv('WHAT_MOVED_PATH') ?: dirname(__DIR__, 3).'/what-moved', '/');
    }

    /**
     * @param  array<string, mixed>|null  $config  null runs without a config file
     */
    public static function analyse(string $root, ?array $config = [], string $source = 'app', string $namespace = 'App'): Analysis
    {
        $json = self::run(['php', self::path().'/scripts/graph.php', $root, self::config($config)], $source, $namespace);

        return new Analysis(json_decode($json, true, flags: JSON_THROW_ON_ERROR));
    }

    /**
     * Both sides are read with the same config, as the action reads them with the one of the head.
     *
     * @param  array<string, mixed>|null  $config
     */
    public static function pullRequest(string $base, string $head, ?array $config = [], string $source = 'app', string $namespace = 'App'): PullRequest
    {
        $configPath = self::config($config);
        $work = Project::temporaryFolder();
        file_put_contents("{$work}/base.json", self::run(['php', self::path().'/scripts/graph.php', $base, $configPath], $source, $namespace));
        file_put_contents("{$work}/head.json", self::run(['php', self::path().'/scripts/graph.php', $head, $configPath], $source, $namespace));
        self::run(['php', self::path().'/scripts/report.php', "{$work}/base.json", "{$work}/head.json", $configPath, "{$work}/out"], $source, $namespace);

        return new PullRequest(
            file_get_contents("{$work}/out/summary.md"),
            file_get_contents("{$work}/out/graph.svg"),
            file_get_contents("{$work}/out/report.html"),
        );
    }

    /**
     * @param  array<string, mixed>|null  $config
     */
    private static function config(?array $config): string
    {
        $path = Project::temporaryFolder().'/what-moved.json';

        if ($config !== null) {
            file_put_contents($path, json_encode((object) $config, JSON_THROW_ON_ERROR));
        }

        return $path;
    }

    /**
     * @param  list<string>  $command
     */
    private static function run(array $command, string $source, string $namespace): string
    {
        $env = [...getenv(), 'WHAT_MOVED_SOURCE' => $source, 'WHAT_MOVED_NAMESPACE' => $namespace];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);

        if (proc_close($process) !== 0) {
            throw new RuntimeException(sprintf("%s failed:\n%s%s", basename($command[1]), $output, $errors));
        }

        return $output;
    }
}
