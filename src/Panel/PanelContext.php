<?php

namespace Upsoftware\Svarium\Panel;

namespace Upsoftware\Svarium\Panel;

use Illuminate\Http\Request;

class PanelContext
{
    public array $params = [];

    protected array $validated = [];

    public function __construct(
        public string $panel,
        protected Request $request,
        array $params = []
    ) {
        $this->params = $params;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function method(): string
    {
        return $this->request->getMethod();
    }

    public function isGet(): bool
    {
        return $this->request->isMethod('GET');
    }

    public function isPost(): bool
    {
        return $this->request->isMethod('POST');
    }

    public function input(string $key = null, $default = null)
    {
        return $this->request->input($key, $default);
    }

    public function all(): array
    {
        return $this->request->all();
    }

    public function validate(array $rules): array
    {
        $this->validated = validator(
            $this->request->all(),
            $rules
        )->validate();

        return $this->validated;
    }

    public function validated(): array
    {
        return $this->validated;
    }
}
