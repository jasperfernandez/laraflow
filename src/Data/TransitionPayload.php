<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Data;

final readonly class TransitionPayload
{
    public function __construct(
        public ?int $actedByPersonId = null,
        public ?int $actedByPositionId = null,
        public ?string $remarks = null,
        public array $metadata = [],
    ) {}
}
