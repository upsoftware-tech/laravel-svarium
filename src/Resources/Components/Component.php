<?php

namespace Upsoftware\Svarium\Resources\Components;

abstract class Component
{
    protected ?string $name = null;
    protected string $component = '';
    protected string $className = '';

    public function __construct(string|array $name = null)
    {
        $this->name = $name;
    }

    function is_multidimensional(array $array): bool {
        return count($array) !== count($array, COUNT_RECURSIVE);
    }

    public static function make(string|array $name = null): static
    {
        return new static($name);
    }

    public function renderComponent(string | array $children): string | array {
        return is_array($children) ? collect($children)->map(function ($item) {
            return $item instanceof Component ? $item->toArray() : $item;
        })->all()
            : $children;
    }

    public function toArray(): array
    {
        $array = [
            'component' => ucfirst($this->component),
        ];

        if ($this->name) {
            $array['name'] = $this->name;
        }

        if ($this->className) {
            $array['class'] = $this->className;
        }

        return $array;
    }
}
