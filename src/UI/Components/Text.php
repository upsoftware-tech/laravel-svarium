<?php

namespace Upsoftware\Svarium\UI\Components;
use Upsoftware\Svarium\UI\Component;

class Text extends Component
{
    public function __construct(?string $text = null)
    {
        if ($text !== null) {
            $this->prop('text', $text);
        }
    }

    public static function make(?string $text = null): static
    {
        return new static($text);
    }
}
