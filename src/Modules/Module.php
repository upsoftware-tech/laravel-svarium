<?php

namespace Upsoftware\Svarium\Modules;

abstract class Module
{
    protected string $path;

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function path(string $append = ''): string
    {
        return $this->path . ($append ? DIRECTORY_SEPARATOR.$append : '');
    }

    abstract public function name(): string;

    public function register(): void {}
    public function boot(): void {}
}
