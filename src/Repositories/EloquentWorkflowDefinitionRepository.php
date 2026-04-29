<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Repositories;

use Illuminate\Database\Eloquent\Model;
use JasperFernandez\Laraflow\Contracts\WorkflowDefinitionRepository;
use JasperFernandez\Laraflow\Data\ActionData;
use JasperFernandez\Laraflow\Data\StepData;
use JasperFernandez\Laraflow\Data\TemplateData;
use JasperFernandez\Laraflow\Models\WorkflowTemplate;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStep;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStepAction;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStepAssignment;

final class EloquentWorkflowDefinitionRepository implements WorkflowDefinitionRepository
{
    public function findByTemplateCode(string $templateCode): ?TemplateData
    {
        $template = WorkflowTemplate::query()
            ->with([
                'steps.assignments.role',
                'steps.actions.action',
                'steps.actions.nextTemplateStep',
                'steps.actions.resultingStepStatus',
                'steps.actions.resultingSubjectStatus',
            ])
            ->where('template_code', $templateCode)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return null;
        }

        $steps = [];

        foreach ($template->steps as $stepModel) {
            /** @var WorkflowTemplateStep $stepModel */
            $actions = [];

            foreach ($stepModel->actions as $actionModel) {
                /** @var WorkflowTemplateStepAction $actionModel */
                $actionCode = $this->stringAttribute($actionModel->action, 'code');

                $actions[$actionCode] = new ActionData(
                    templateStepActionId: $actionModel->id,
                    actionId: $actionModel->action_id,
                    actionCode: $actionCode,
                    nextTemplateStepId: $actionModel->next_workflow_template_step_id,
                    nextStepCode: $actionModel->nextTemplateStep?->step_code,
                    completesStep: $actionModel->completes_step,
                    closesWorkflow: $actionModel->closes_workflow,
                    resultingStepStatusId: $actionModel->resulting_step_status_id,
                    resultingStepStatusCode: $this->nullableStringAttribute(
                        $actionModel->resultingStepStatus,
                        'code',
                    ),
                    resultingSubjectStatusId: $actionModel->resulting_subject_status_id,
                    resultingSubjectStatusCode: $this->nullableStringAttribute(
                        $actionModel->resultingSubjectStatus,
                        'code',
                    ),
                );
            }

            $steps[] = new StepData(
                id: $stepModel->id,
                code: $stepModel->step_code,
                name: $stepModel->step_name,
                sequenceNo: $stepModel->sequence_no,
                assignmentRoleNames: $stepModel->assignments
                    ->filter(fn (WorkflowTemplateStepAssignment $assignment): bool => $assignment->role !== null)
                    ->map(
                        fn (WorkflowTemplateStepAssignment $assignment): string => $this->stringAttribute(
                            $assignment->role,
                            'name',
                        )
                    )
                    ->values()
                    ->all(),
                actions: $actions,
            );
        }

        return new TemplateData(
            id: $template->id,
            code: $template->template_code,
            name: $template->template_name,
            steps: $steps,
        );
    }

    private function stringAttribute(?Model $model, string $key): string
    {
        return (string) $model?->getAttribute($key);
    }

    private function nullableStringAttribute(?Model $model, string $key): ?string
    {
        $value = $model?->getAttribute($key);

        return $value === null ? null : (string) $value;
    }
}
