<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use JasperFernandez\Laraflow\Contracts\WorkflowAuthorization;
use JasperFernandez\Laraflow\Contracts\WorkflowDefinitionRepository;
use JasperFernandez\Laraflow\Data\TransitionPayload;
use JasperFernandez\Laraflow\Data\TransitionResult;
use JasperFernandez\Laraflow\Exceptions\InvalidActionException;
use JasperFernandez\Laraflow\Exceptions\UnauthorizedActionException;
use JasperFernandez\Laraflow\Exceptions\WorkflowDefinitionException;
use JasperFernandez\Laraflow\Exceptions\WorkflowStateException;
use JasperFernandez\Laraflow\Models\WorkflowInstance;
use JasperFernandez\Laraflow\Models\WorkflowInstanceStep;

final readonly class WorkflowTransitioner
{
    public function __construct(
        private WorkflowDefinitionRepository $definitions,
        private WorkflowAuthorization $authorization,
        private WorkflowStepFactory $stepFactory,
        private WorkflowAssignmentSynchronizer $assignmentSynchronizer,
        private WorkflowTransitionRecorder $transitionRecorder,
    ) {}

    public function apply(
        WorkflowInstance $instance,
        string $actionCode,
        Authenticatable $actor,
        ?TransitionPayload $payload = null,
    ): TransitionResult {
        $payload ??= new TransitionPayload;

        if ($instance->is_closed) {
            throw new WorkflowStateException('Workflow instance is already closed.');
        }

        $instance->loadMissing(['currentStep.templateStep', 'template']);

        $templateCode = (string) $instance->template->template_code;
        $template = $this->definitions->findByTemplateCode($templateCode);

        if ($template === null) {
            throw new WorkflowDefinitionException("Workflow template [{$templateCode}] was not found.");
        }

        $currentTemplateStepId = $instance->current_workflow_template_step_id;

        if ($currentTemplateStepId === null) {
            throw new WorkflowStateException('Workflow instance has no current template step.');
        }

        $stepDefinition = $template->findStepById($currentTemplateStepId);

        if ($stepDefinition === null) {
            throw new WorkflowDefinitionException("Step definition [{$currentTemplateStepId}] was not found.");
        }

        $action = $stepDefinition->findAction($actionCode);

        if ($action === null) {
            throw new InvalidActionException(
                "Action [{$actionCode}] is not allowed for step [{$stepDefinition->code}]."
            );
        }

        if (! $this->authorization->canExecute($instance, $stepDefinition, $action, $actor)) {
            throw new UnauthorizedActionException(
                "Actor is not allowed to execute [{$actionCode}] on step [{$stepDefinition->code}]."
            );
        }

        /** @var WorkflowInstanceStep|null $fromStep */
        $fromStep = $instance->currentStep;

        if ($fromStep === null) {
            throw new WorkflowStateException('Workflow instance has no current runtime step.');
        }

        return DB::transaction(function () use ($instance, $fromStep, $template, $action, $actor, $payload): TransitionResult {
            $fromApplicationStatusId = $instance->application_status_id;
            $fromStepStatusId = $fromStep->status_id;

            $fromStep->forceFill([
                'status_id' => $action->resultingStepStatusId,
                'remarks' => $payload->remarks,
                'completed_at' => $action->completesStep ? CarbonImmutable::now() : $fromStep->completed_at,
                'closed_at' => $action->completesStep ? CarbonImmutable::now() : $fromStep->closed_at,
            ])->save();

            $toStep = null;

            if ($action->nextTemplateStepId !== null) {
                $nextStepDefinition = $template->findStepById($action->nextTemplateStepId);

                if ($nextStepDefinition === null) {
                    throw new WorkflowDefinitionException(
                        "Next step definition [{$action->nextTemplateStepId}] was not found."
                    );
                }

                $toStep = $this->stepFactory->create($instance, $nextStepDefinition);
                $this->assignmentSynchronizer->syncForStep($toStep, $nextStepDefinition);

                $instance->forceFill([
                    'current_workflow_instance_step_id' => $toStep->id,
                    'current_workflow_template_step_id' => $nextStepDefinition->id,
                    'application_status_id' => $action->resultingApplicationStatusId,
                    'is_closed' => false,
                    'completed_at' => null,
                    'closed_at' => null,
                ])->save();
            } else {
                $instance->forceFill([
                    'application_status_id' => $action->resultingApplicationStatusId,
                    'is_closed' => $action->closesApplication,
                    'completed_at' => $action->closesApplication ? CarbonImmutable::now() : $instance->completed_at,
                    'closed_at' => $action->closesApplication ? CarbonImmutable::now() : $instance->closed_at,
                ])->save();

                if ($action->closesApplication) {
                    $instance->forceFill([
                        'current_workflow_instance_step_id' => $fromStep->id,
                    ])->save();
                }
            }

            $transition = $this->transitionRecorder->record(
                instance: $instance,
                fromStep: $fromStep->fresh(),
                toStep: $toStep,
                action: $action,
                actor: $actor,
                payload: $payload,
                fromStepStatusId: $fromStepStatusId,
                toStepStatusId: $action->resultingStepStatusId,
                fromApplicationStatusId: $fromApplicationStatusId,
                toApplicationStatusId: $action->resultingApplicationStatusId,
            );

            $instance->refresh();

            return new TransitionResult(
                instance: $instance->fresh(['currentStep', 'steps', 'transitions']),
                fromStep: $fromStep->fresh(),
                toStep: $toStep?->fresh(),
                transition: $transition,
                closed: (bool) $instance->is_closed,
            );
        });
    }
}
