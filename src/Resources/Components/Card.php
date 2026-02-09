<?php

namespace Upsoftware\Svarium\Resources\Components;

class Card extends Component
{
    protected string $component = 'card';
    protected string|array $content;

    public function content(array | string $content): self {
        $this->content = $content;
        return $this;
    }

    public function toArray(): array
    {

        return [
            ...parent::toArray(),
            'props' => [
                'header'    => '',
                'content'   => $this->renderComponent($this->content),
                'footer'    => '',
            ]
        ];
    }
}
