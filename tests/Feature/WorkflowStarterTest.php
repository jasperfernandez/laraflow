<?php

declare(strict_types=1);

use JasperFernandez\Laraflow\Exceptions\WorkflowDefinitionException;
use JasperFernandez\Laraflow\Models\WorkflowTemplate;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStep;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStepAssignment;
use JasperFernandez\Laraflow\Services\WorkflowEngine;
use JasperFernandez\Laraflow\Tests\Support\Models\DummySubject;
use JasperFernandez\Laraflow\Tests\Support\Models\Role;

it('throws when starting an unknown workflow template', function () {
    $subject = DummySubject::query()->create(['name' => 'Unknown Template Subject']);

    expect(fn () => app(WorkflowEngine::class)->start('UNKNOWN-TEMPLATE', $subject))
        ->toThrow(WorkflowDefinitionException::class, 'Workflow template [UNKNOWN-TEMPLATE] was not found.');
});

it('throws when a workflow template has no steps', function () {
    WorkflowTemplate::query()->create([
        'template_code' => 'NO-STEPS',
        'template_name' => 'No Steps Workflow',
        'description' => 'Workflow without steps',
        'is_active' => true,
    ]);

    $subject = DummySubject::query()->create(['name' => 'No Steps Subject']);

    expect(fn () => app(WorkflowEngine::class)->start('NO-STEPS', $subject))
        ->toThrow(WorkflowDefinitionException::class, 'Workflow template [NO-STEPS] has no steps.');
});

it('starts from the lowest sequence step and syncs only roles that exist', function () {
    $existingRole = Role::query()->create(['name' => 'member']);

    $template = WorkflowTemplate::query()->create([
        'template_code' => 'OUT-OF-ORDER',
        'template_name' => 'Out Of Order Workflow',
        'description' => 'Ensures the first step is sequence based',
        'is_active' => true,
    ]);

    $laterStep = WorkflowTemplateStep::query()->create([
        'workflow_template_id' => $template->id,
        'step_code' => 'SECOND',
        'step_name' => 'Second Step',
        'sequence_no' => 2,
        'is_active' => true,
    ]);

    $firstStep = WorkflowTemplateStep::query()->create([
        'workflow_template_id' => $template->id,
        'step_code' => 'FIRST',
        'step_name' => 'First Step',
        'sequence_no' => 1,
        'is_active' => true,
    ]);

    WorkflowTemplateStepAssignment::query()->create([
        'workflow_template_step_id' => $firstStep->id,
        'role_id' => $existingRole->id,
    ]);

    WorkflowTemplateStepAssignment::query()->create([
        'workflow_template_step_id' => $laterStep->id,
        'role_id' => $existingRole->id,
    ]);

    $missingRoleId = $existingRole->id + 999;

    WorkflowTemplateStepAssignment::query()->create([
        'workflow_template_step_id' => $firstStep->id,
        'role_id' => $missingRoleId,
    ]);

    $subject = DummySubject::query()->create(['name' => 'Ordered Subject']);

    $instance = app(WorkflowEngine::class)->start('OUT-OF-ORDER', $subject, ['source' => 'test']);

    expect($instance->current_workflow_template_step_id)->toBe($firstStep->id)
        ->and($instance->currentStep->workflow_template_step_id)->toBe($firstStep->id)
        ->and($instance->context)->toBe(['source' => 'test'])
        ->and($instance->steps)->toHaveCount(1)
        ->and($instance->currentStep->assignments)->toHaveCount(1)
        ->and($instance->currentStep->assignments->first()->role_id)->toBe($existingRole->id);

    $this->assertDatabaseHas('workflow_instances', [
        'id' => $instance->id,
        'current_workflow_template_step_id' => $firstStep->id,
        'subject_type' => $subject->getMorphClass(),
        'subject_id' => $subject->id,
    ]);

    $this->assertDatabaseMissing('workflow_instance_step_assignments', [
        'workflow_instance_step_id' => $instance->current_workflow_instance_step_id,
        'role_id' => $missingRoleId,
    ]);
});
