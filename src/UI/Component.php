<?php

namespace Upsoftware\Svarium\UI;

abstract class Component
{
    protected array $props = [];
    protected array $children = [];
    protected array $slots = [];

    public static function make(): static
    {
        return new static;
    }

    public function prop(string $key, mixed $value): static
    {
        $this->props[$key] = $value;
        return $this;
    }

    public function slot(string $name, Component|array|string|\Closure|null $content): static
    {
        $this->slots[$name] = $content;
        return $this;
    }

    public function content(array $children): static
    {
        $this->children = $children;
        return $this;
    }

    protected function resolveSlot(mixed $content): array
    {
        // jeśli podano nazwę klasy
        if (is_string($content) && class_exists($content)) {
            $instance = app($content);

            // LayoutSection → budujemy
            if ($instance instanceof \Upsoftware\Svarium\UI\Contracts\LayoutSection) {
                $content = $instance->build();
            }
            // Component → używamy bezpośrednio
            elseif ($instance instanceof Component) {
                $content = $instance;
            }
            else {
                return [];
            }
        }

        // closure
        if ($content instanceof \Closure) {
            $content = $content();
        }

        // pojedynczy komponent
        if ($content instanceof Component) {
            $array = $content->toArray();

            // jeżeli komponent ma slot 'content' i brak children → traktuj jak wrapper
            if (!empty($content->slots['content'] ?? null)) {
                $array['slots']['content'] = array_map(
                    fn ($c) => $c->toArray(),
                    $content->slots['content']
                );
            }

            return [$array];
        }

        // tablica komponentów
        if (is_array($content)) {
            return array_map(fn ($c) => $c->toArray(), $content);
        }

        return [];
    }

    protected function slotOrChildren(string $name): array
    {
        if (!empty($this->slots[$name])) {
            return array_map(fn ($c) => $c->toArray(), $this->slots[$name]);
        }

        return array_map(fn ($c) => $c->toArray(), $this->children);
    }

    public function toArray(): array
    {
        return [
            'type' => class_basename(static::class),
            'props' => $this->props,
            'children' => array_map(fn ($c) => $c->toArray(), $this->children),
            'slots' => collect($this->slots)->map(
                fn ($content) => $this->resolveSlot($content)
            )->toArray(),
        ];
    }
}
