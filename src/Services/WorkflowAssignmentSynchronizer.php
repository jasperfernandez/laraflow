<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Services;

use Carbon\CarbonImmutable;
use JasperFernandez\Laraflow\Data\StepData;
use JasperFernandez\Laraflow\Models\WorkflowInstanceStep;
use JasperFernandez\Laraflow\Models\WorkflowInstanceStepAssignment;

final class WorkflowAssignmentSynchronizer
{
    public function syncForStep(WorkflowInstanceStep $instanceStep, StepData $stepData): void
    {
        foreach ($stepData->assigneeRoleNames as $roleName) {
            $role = config('laraflow.models.role')::query()->where('name', $roleName)->first();

            if ($role === null) {
                continue;
            }

            WorkflowInstanceStepAssignment::query()->firstOrCreate(
                [
                    'workflow_instance_step_id' => $instanceStep->id,
                    'role_id' => $role->id,
                ],
                [
                    'assigned_at' => CarbonImmutable::now(),
                    'is_active' => true,
                ],
            );
        }
    }
}
