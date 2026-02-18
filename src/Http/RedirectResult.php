<?php

namespace Upsoftware\Svarium\Http;

use Symfony\Component\HttpFoundation\Response;

class RedirectResult implements OperationResult
{
    public function __construct(
        protected string $uri
    ) {}

    public function toResponse(): Response
    {
        return redirect($this->uri);
    }
}
