<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use JasperFernandez\Laraflow\Data\ActionData;
use JasperFernandez\Laraflow\Data\TransitionPayload;
use JasperFernandez\Laraflow\Models\WorkflowInstance;
use JasperFernandez\Laraflow\Models\WorkflowInstanceStep;
use JasperFernandez\Laraflow\Models\WorkflowInstanceTransition;

final class WorkflowTransitionRecorder
{
    public function record(
        WorkflowInstance $instance,
        WorkflowInstanceStep $fromStep,
        ?WorkflowInstanceStep $toStep,
        ActionData $action,
        Authenticatable $actor,
        TransitionPayload $payload,
        ?int $fromStepStatusId,
        ?int $toStepStatusId,
        ?int $fromApplicationStatusId,
        ?int $toApplicationStatusId,
    ): WorkflowInstanceTransition {
        /** @var Model $actorModel */
        $actorModel = $actor;

        return WorkflowInstanceTransition::query()->create([
            'workflow_instance_id' => $instance->id,
            'from_workflow_instance_step_id' => $fromStep->id,
            'to_workflow_instance_step_id' => $toStep?->id,
            'workflow_template_step_action_id' => $action->templateStepActionId,
            'action_id' => $action->actionId,
            'actor_type' => $actorModel->getMorphClass(),
            'actor_id' => $actorModel->getKey(),
            'from_step_status_id' => $fromStepStatusId,
            'to_step_status_id' => $toStepStatusId,
            'from_application_status_id' => $fromApplicationStatusId,
            'to_application_status_id' => $toApplicationStatusId,
            'remarks' => $payload->remarks,
            'metadata' => $payload->metadata,
            'acted_at' => CarbonImmutable::now(),
        ]);
    }
}
