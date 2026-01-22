<?php

namespace Upsoftware\Svarium\Fields;

use Upsoftware\Svarium\JsonResource\Field;

class Column extends Field
{
    public function type(): string
    {
        return 'column';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'type' => $this->type(),
            'label' => $this->label ?? $this->name,
            'sortable' => true,
            'meta' => $this->meta,
        ]);
    }
}
