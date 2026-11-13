<?php

namespace Tests\Support;

/**
 * A PHP project on disk for one test: written from a list of files, or copied from a fixture and then changed,
 * the way a pull request changes the base into the head.
 */
final class Project
{
    private function __construct(public readonly string $root) {}

    /**
     * @param  array<string, string>  $files  path from the project root => code
     */
    public static function with(array $files): self
    {
        return (new self(self::temporaryFolder()))->write($files);
    }

    public static function copyOf(string $fixture): self
    {
        $project = new self(self::temporaryFolder());
        $from = __DIR__.'/../Fixtures/'.$fixture;
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            $project->write([substr($file->getPathname(), strlen($from) + 1) => file_get_contents($file->getPathname())]);
        }

        return $project;
    }

    /**
     * @param  array<string, string>  $files
     */
    public function write(array $files): self
    {
        foreach ($files as $path => $code) {
            $folder = dirname("{$this->root}/{$path}");

            if (! is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            file_put_contents("{$this->root}/{$path}", $code);
        }

        return $this;
    }

    public function delete(string $path): self
    {
        unlink("{$this->root}/{$path}");

        return $this;
    }

    public function move(string $from, string $to, string $code): self
    {
        return $this->delete($from)->write([$to => $code]);
    }

    public static function temporaryFolder(): string
    {
        $folder = sys_get_temp_dir().'/what-moved-tests/'.bin2hex(random_bytes(8));
        mkdir($folder, 0777, true);

        return $folder;
    }
}
