<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Repositories;

use JasperFernandez\Laraflow\Contracts\WorkflowDefinitionRepository;
use JasperFernandez\Laraflow\Data\ActionData;
use JasperFernandez\Laraflow\Data\StepData;
use JasperFernandez\Laraflow\Data\TemplateData;
use JasperFernandez\Laraflow\Models\WorkflowTemplate;

final class EloquentWorkflowDefinitionRepository implements WorkflowDefinitionRepository
{
    public function findByTemplateCode(string $templateCode): ?TemplateData
    {
        $template = WorkflowTemplate::query()
            ->with([
                'steps.assignees.role',
                'steps.actions.action',
                'steps.actions.nextTemplateStep',
                'steps.actions.resultingStepStatus',
                'steps.actions.resultingApplicationStatus',
            ])
            ->where('template_code', $templateCode)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return null;
        }

        $steps = [];

        foreach ($template->steps as $stepModel) {
            $actions = [];

            foreach ($stepModel->actions as $actionModel) {
                $actionCode = (string) $actionModel->action->code;

                $actions[$actionCode] = new ActionData(
                    templateStepActionId: $actionModel->id,
                    actionId: $actionModel->action_id,
                    actionCode: $actionCode,
                    nextTemplateStepId: $actionModel->next_workflow_template_step_id,
                    nextStepCode: $actionModel->nextTemplateStep?->step_code,
                    completesStep: (bool) $actionModel->completes_step,
                    closesApplication: (bool) $actionModel->closes_application,
                    resultingStepStatusId: $actionModel->resulting_step_status_id,
                    resultingStepStatusCode: $actionModel->resultingStepStatus?->code,
                    resultingApplicationStatusId: $actionModel->resulting_application_status_id,
                    resultingApplicationStatusCode: $actionModel->resultingApplicationStatus?->code,
                );
            }

            $steps[] = new StepData(
                id: $stepModel->id,
                code: (string) $stepModel->step_code,
                name: (string) $stepModel->step_name,
                sequenceNo: (int) $stepModel->sequence_no,
                assigneeRoleNames: $stepModel->assignees
                    ->map(fn ($assignee): string => (string) $assignee->role->name)
                    ->values()
                    ->all(),
                actions: $actions,
            );
        }

        return new TemplateData(
            id: $template->id,
            code: (string) $template->template_code,
            name: (string) $template->template_name,
            steps: $steps,
        );
    }
}
