<?php

namespace Upsoftware\Svarium\JsonResource;

abstract class Field
{
    public string $name;
    public string $label;
    public string $attribute;
    public array $meta = [];
    public array $dependencies = [];
    public bool $isAccessor = false;
    public bool $visible = true;
    public string $relation;
    public bool $isRelation = false;
    public ?string $dateFormat = null;
    public array $validationRules = [];

    public function __construct(string $name, ?string $attribute = null)
    {
        $this->name = $name;
        $this->relation = '';
        $this->attribute = $attribute ?? strtolower($name);
    }

    public static function make(string $name, ?string $attribute = null): static
    {
        return new static($name, $attribute);
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
        $finalAttribute = $this->isRelation && !str_contains($this->attribute, '.')
            ? $this->relation . '.' . $this->attribute
            : $this->attribute;

        return [
            'type' => $this->type(),
            'visible' => $this->visible,
            'attribute' => $finalAttribute,
            'label' => $this->label ?? $this->name,
            'meta' => $this->meta,
            'relation' => $this->relation,
            'is_accessor' => $this->isAccessor,
            'is_relation' => $this->isRelation,
            'date_format' => $this->dateFormat,
        ];
    }
}
