<?php

namespace Upsoftware\Svarium\Resources\Components;

class Grid extends Block
{
    public string $component = 'grid';
    public array $cols = [];
    public int|array|null $grid = null;
    public int|array|null $gridX = null;
    public int|array|null $gridY = null;

    public function cols(int|string $col, ?int $value = null): static
    {
        if (is_numeric($col)) {
            $this->cols['default'] = $col;
        } else if (is_string($col)) {
            $this->cols[$col] = $value;
        }
        return $this;
    }

    public function props() {
        $array = [];
        $array["cols"] = $this->cols;
        return $array;
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'props' => $this->props()
        ];
    }
}
