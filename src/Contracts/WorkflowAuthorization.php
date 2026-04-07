<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use JasperFernandez\Laraflow\Data\ActionData;
use JasperFernandez\Laraflow\Data\StepData;
use JasperFernandez\Laraflow\Models\WorkflowInstance;

interface WorkflowAuthorization
{
    public function canExecute(
        WorkflowInstance $instance,
        StepData $step,
        ActionData $action,
        Authenticatable $actor,
    ): bool;
}
