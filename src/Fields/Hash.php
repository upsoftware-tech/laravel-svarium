<?php

namespace Upsoftware\Svarium\Fields;

use Upsoftware\Svarium\JsonResource\Field;

class Hash extends Field
{
    public function __construct()
    {
        $this->name = 'hash';
        $this->relation = '';
        $this->key = $key ?? strtolower($this->name);
        $this->visible = false;
    }
    public function type(): string
    {
        return 'hash';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'name' => 'hash',
            'type' => $this->type(),
            'label' => $this->label ?? $this->name,
            'sortable' => false,
            'system' => true,
            'visible' => false,
        ]);
    }
}
