<?php

declare(strict_types=1);

use JasperFernandez\Laraflow\Models\WorkflowTemplate;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStep;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStepAction;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStepAssignment;
use JasperFernandez\Laraflow\Repositories\EloquentWorkflowDefinitionRepository;
use JasperFernandez\Laraflow\Tests\Support\Models\Action;
use JasperFernandez\Laraflow\Tests\Support\Models\Role;
use JasperFernandez\Laraflow\Tests\Support\Models\Status;

it('returns null for inactive templates', function () {
    WorkflowTemplate::query()->create([
        'template_code' => 'INACTIVE',
        'template_name' => 'Inactive Template',
        'description' => 'Should not be resolved',
        'is_active' => false,
    ]);

    $repository = new EloquentWorkflowDefinitionRepository;

    expect($repository->findByTemplateCode('INACTIVE'))->toBeNull();
});

it('maps workflow templates into data objects', function () {
    $memberRole = Role::query()->create(['name' => 'member']);
    $reviewerRole = Role::query()->create(['name' => 'reviewer']);

    $submitAction = Action::query()->create(['code' => 'submit_application', 'name' => 'Submit Application']);

    $completedStatus = Status::query()->create(['code' => 'completed', 'name' => 'Completed']);
    $pendingStatus = Status::query()->create(['code' => 'pending_review', 'name' => 'Pending Review']);

    $template = WorkflowTemplate::query()->create([
        'template_code' => 'MEMBERSHIP-APPLICATION',
        'template_name' => 'Membership Application Workflow',
        'description' => 'Repository mapping test',
        'is_active' => true,
    ]);

    $reviewStep = WorkflowTemplateStep::query()->create([
        'workflow_template_id' => $template->id,
        'step_code' => 'REVIEW',
        'step_name' => 'Review',
        'sequence_no' => 2,
        'is_active' => true,
    ]);

    $registrationStep = WorkflowTemplateStep::query()->create([
        'workflow_template_id' => $template->id,
        'step_code' => 'REGISTER',
        'step_name' => 'Register',
        'sequence_no' => 1,
        'is_active' => true,
    ]);

    WorkflowTemplateStepAssignment::query()->create([
        'workflow_template_step_id' => $registrationStep->id,
        'role_id' => $memberRole->id,
    ]);

    WorkflowTemplateStepAssignment::query()->create([
        'workflow_template_step_id' => $registrationStep->id,
        'role_id' => $reviewerRole->id,
    ]);

    WorkflowTemplateStepAction::query()->create([
        'workflow_template_step_id' => $registrationStep->id,
        'action_id' => $submitAction->id,
        'next_workflow_template_step_id' => $reviewStep->id,
        'completes_step' => true,
        'resulting_step_status_id' => $completedStatus->id,
        'resulting_application_status_id' => $pendingStatus->id,
        'closes_application' => false,
    ]);

    $repository = new EloquentWorkflowDefinitionRepository;
    $templateData = $repository->findByTemplateCode('MEMBERSHIP-APPLICATION');

    expect($templateData)->not->toBeNull()
        ->and($templateData->id)->toBe($template->id)
        ->and($templateData->code)->toBe('MEMBERSHIP-APPLICATION')
        ->and($templateData->name)->toBe('Membership Application Workflow')
        ->and($templateData->steps)->toHaveCount(2)
        ->and($templateData->firstStep()?->id)->toBe($registrationStep->id);

    $stepData = $templateData?->findStepByCode('REGISTER');
    $actionData = $stepData?->findAction('submit_application');

    expect($stepData)->not->toBeNull()
        ->and($stepData->assignmentRoleNames)->toBe(['member', 'reviewer'])
        ->and($actionData)->not->toBeNull()
        ->and($actionData->actionId)->toBe($submitAction->id)
        ->and($actionData->nextTemplateStepId)->toBe($reviewStep->id)
        ->and($actionData->nextStepCode)->toBe('REVIEW')
        ->and($actionData->completesStep)->toBeTrue()
        ->and($actionData->closesApplication)->toBeFalse()
        ->and($actionData->resultingStepStatusId)->toBe($completedStatus->id)
        ->and($actionData->resultingStepStatusCode)->toBe('completed')
        ->and($actionData->resultingApplicationStatusId)->toBe($pendingStatus->id)
        ->and($actionData->resultingApplicationStatusCode)->toBe('pending_review');
});
