<?php

namespace Upsoftware\Svarium\JsonResource;

abstract class Field
{
    public string $name;
    public string $label;
    public string $key;
    public array $meta = [];
    public array $dependencies = [];
    public bool $isAccessor = false;
    public bool $visible = true;
    public string $relation;
    public bool $isRelation = false;
    public bool $isSystem = false;
    public ?string $dateFormat = null;
    public array $validationRules = [];
    public ?int $size = null;

    public function __construct(?string $name = null, ?string $key = null)
    {
        $this->name = $name;
        $this->relation = '';
        $this->key = $key ?? strtolower($name);
    }

    public static function make(?string $name = null, ?string $key = null): static
    {
        return new static($name, $key);
    }

    public function sortable(bool $value = true): static
    {
        $this->meta['sortable'] = $value;
        return $this;
    }

    public function isHidden(): static
    {
        $this->visible = false;
        return $this;
    }

    public function size($size): static
    {
        $this->size = $size;
        return $this;
    }

    public function formatDate(string $format): static
    {
        $this->dateFormat = $format;
        return $this;
    }

    public function dependsOn(array|string $attributes): static
    {
        $this->dependencies = is_array($attributes) ? $attributes : func_get_args();
        return $this;
    }

    public function isAccessor(bool $value = true): self
    {
        $this->isAccessor = $value;
        return $this;
    }

    public function relation(string $relation): self
    {
        $this->relation = $relation;
        $this->isRelation = true;
        return $this;
    }

    public function label(string $label) {
        $this->label = $label;
        return $this;
    }

    public function validate(array|string $rules): static
    {
        $this->validationRules = is_string($rules)
            ? explode('|', $rules)
            : $rules;

        return $this;
    }

    abstract public function type(): string;

    public function toArray(): array
    {
        $finalKey = $this->isRelation && !str_contains($this->key, '.')
            ? $this->relation . '.' . $this->key
            : $this->key;

        $data = [
            'type' => $this->type(),
            'visible' => $this->visible,
            'key' => $finalKey,
            'label' => $this->label ?? $this->name,
            'system' => $this->isSystem
        ];

        if ($this->size) {
            $data["size"] = $this->size;
        }

        return $data;
    }
}
