<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Data;

final readonly class StepData
{
    /**
     * @param  array<int, string>  $assigneeRoleNames
     * @param  array<string, ActionData>  $actions
     */
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public int $sequenceNo,
        public array $assigneeRoleNames,
        public array $actions,
    ) {}

    public function findAction(string $actionCode): ?ActionData
    {
        return $this->actions[$actionCode] ?? null;
    }
}
