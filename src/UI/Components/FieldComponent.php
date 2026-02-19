<?php

namespace Upsoftware\Svarium\UI\Components;

use Upsoftware\Svarium\UI\Component;
use Upsoftware\Svarium\UI\Concerns\HasValidation;

abstract class FieldComponent extends Component
{
    use HasValidation;
    protected string $name;

    public function name(string $name): static
    {
        $this->name = $name;
        $this->props['name'] = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
