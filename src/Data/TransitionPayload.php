<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Data;

final readonly class TransitionPayload
{
    public function __construct(
        public ?string $remarks = null,
        public array $metadata = [],
    ) {}
}
