<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use JasperFernandez\Laraflow\Contracts\WorkflowAuthorization;
use JasperFernandez\Laraflow\Data\ActionData;
use JasperFernandez\Laraflow\Data\StepData;
use JasperFernandez\Laraflow\Models\WorkflowInstance;

final class RoleBasedWorkflowAuthorization implements WorkflowAuthorization
{
    public function canExecute(
        WorkflowInstance $instance,
        StepData $step,
        ActionData $action,
        Authenticatable $actor,
    ): bool {
        if (! method_exists($actor, 'hasRole')) {
            return false;
        }

        return array_any($step->assigneeRoleNames, fn ($roleName) => $actor->hasRole($roleName));

    }
}
