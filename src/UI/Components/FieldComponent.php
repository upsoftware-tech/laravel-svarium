<?php

namespace Upsoftware\Svarium\UI\Components;

use Upsoftware\Svarium\UI\Component;
use Upsoftware\Svarium\UI\Concerns\HasValidation;

abstract class FieldComponent extends Component
{
    use HasValidation;

    protected ?string $name = null;
    protected ?string $label = null;

    public function __construct(?string $name = null)
    {
        $this->name = $name;

        if ($name) {
            $this->props['name'] = $name;
        }
    }

    public static function make(?string $name = null): static
    {
        return new static($name);
    }

    public function getName(): ?string
    {
        return $this->name;
    }
    public function label(string $label): static
    {
        $this->label = $label;
        $this->props['label'] = $label;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }
}
