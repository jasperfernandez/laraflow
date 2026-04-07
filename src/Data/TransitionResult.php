<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Data;

use JasperFernandez\Laraflow\Models\WorkflowInstance;
use JasperFernandez\Laraflow\Models\WorkflowInstanceStep;
use JasperFernandez\Laraflow\Models\WorkflowInstanceTransition;

final readonly class TransitionResult
{
    public function __construct(
        public WorkflowInstance $instance,
        public WorkflowInstanceStep $fromStep,
        public ?WorkflowInstanceStep $toStep,
        public WorkflowInstanceTransition $transition,
        public bool $closed,
    ) {}
}
