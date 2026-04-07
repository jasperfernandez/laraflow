<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JasperFernandez\Laraflow\Contracts\WorkflowDefinitionRepository;
use JasperFernandez\Laraflow\Exceptions\WorkflowDefinitionException;
use JasperFernandez\Laraflow\Models\WorkflowInstance;

final readonly class WorkflowStarter
{
    public function __construct(
        private WorkflowDefinitionRepository $definitions,
        private WorkflowStepFactory $stepFactory,
        private WorkflowAssignmentSynchronizer $assignmentSynchronizer,
    ) {}

    public function start(string $templateCode, Model $subject, array $context = []): WorkflowInstance
    {
        $template = $this->definitions->findByTemplateCode($templateCode);

        if ($template === null) {
            throw new WorkflowDefinitionException("Workflow template [{$templateCode}] was not found.");
        }

        $firstStep = $template->firstStep();

        if ($firstStep === null) {
            throw new WorkflowDefinitionException("Workflow template [{$templateCode}] has no steps.");
        }

        return DB::transaction(function () use ($template, $firstStep, $subject, $context): WorkflowInstance {
            $instance = WorkflowInstance::query()->create([
                'workflow_template_id' => $template->id,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'current_workflow_instance_step_id' => null,
                'current_workflow_template_step_id' => $firstStep->id,
                'application_status_id' => null,
                'is_closed' => false,
                'started_at' => CarbonImmutable::now(),
                'context' => $context,
            ]);

            $instanceStep = $this->stepFactory->create($instance, $firstStep);

            $this->assignmentSynchronizer->syncForStep($instanceStep, $firstStep);

            $instance->forceFill([
                'current_workflow_instance_step_id' => $instanceStep->id,
            ])->save();

            return $instance->fresh(['currentStep', 'steps', 'transitions']);
        });
    }
}
