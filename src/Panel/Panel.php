<?php

namespace Upsoftware\Svarium\Panel;

class Panel
{
    public function __construct(
        public string $name,
        public ?string $prefix = null,
    ) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function prefix(string $prefix): static
    {
        $this->prefix = trim($prefix, '/');
        return $this;
    }

    public function noPrefix(): static
    {
        $this->prefix = null;
        return $this;
    }
}
