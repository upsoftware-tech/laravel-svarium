<?php

namespace Upsoftware\Svarium\Fields;

use Upsoftware\Svarium\JsonResource\Field;

class Text extends Field
{
    public function type(): string
    {
        return 'text';
    }
}
