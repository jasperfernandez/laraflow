<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Services;

use Carbon\CarbonImmutable;
use JasperFernandez\Laraflow\Data\StepData;
use JasperFernandez\Laraflow\Models\WorkflowInstance;
use JasperFernandez\Laraflow\Models\WorkflowInstanceStep;

final class WorkflowStepFactory
{
    public function create(WorkflowInstance $instance, StepData $stepData): WorkflowInstanceStep
    {
        return WorkflowInstanceStep::query()->create([
            'workflow_instance_id' => $instance->id,
            'workflow_template_step_id' => $stepData->id,
            'sequence_no' => $stepData->sequenceNo,
            'status_id' => null,
            'remarks' => null,
            'opened_at' => CarbonImmutable::now(),
        ]);
    }
}
