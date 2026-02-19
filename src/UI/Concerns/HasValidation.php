<?php

namespace Upsoftware\Svarium\UI\Concerns;

trait HasValidation
{
    protected array $rules = [];

    public function rule(string $rule): static
    {
        $this->rules[] = $rule;
        return $this;
    }

    public function required(): static
    {
        return $this->rule('required');
    }

    public function nullable(): static
    {
        return $this->rule('nullable');
    }

    public function min(int $value): static
    {
        return $this->rule("min:$value");
    }

    public function max(int $value): static
    {
        return $this->rule("max:$value");
    }

    public function email(): static
    {
        return $this->rule('email');
    }

    public function getValidationRules(): array
    {
        return $this->rules;
    }
}
