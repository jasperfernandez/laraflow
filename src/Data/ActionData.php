<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Data;

final readonly class ActionData
{
    public function __construct(
        public int $templateStepActionId,
        public int $actionId,
        public string $actionCode,
        public ?int $nextTemplateStepId,
        public ?string $nextStepCode,
        public bool $completesStep,
        public bool $closesWorkflow,
        public ?int $resultingStepStatusId,
        public ?string $resultingStepStatusCode,
        public ?int $resultingSubjectStatusId,
        public ?string $resultingSubjectStatusCode,
    ) {}
}
