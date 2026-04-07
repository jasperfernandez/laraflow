<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use JasperFernandez\Laraflow\Models\WorkflowTemplate;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStep;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStepAssignment;
use JasperFernandez\Laraflow\Services\WorkflowEngine;
use JasperFernandez\Laraflow\Tests\Support\Models\DummySubject;

it('can start a workflow instance', function () {
    $roleId = DB::table('roles')->insertGetId([
        'name' => 'member',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $template = WorkflowTemplate::query()->create([
        'template_code' => 'MEMBERSHIP-APPLICATION',
        'template_name' => 'Membership Application Workflow',
        'description' => 'Test workflow',
        'is_active' => true,
    ]);

    $step = WorkflowTemplateStep::query()->create([
        'workflow_template_id' => $template->id,
        'step_code' => 'APPLICANT_REGISTRATION',
        'step_name' => 'Applicant Registration',
        'step_description' => 'Initial step',
        'sequence_no' => 1,
        'is_active' => true,
    ]);

    WorkflowTemplateStepAssignment::query()->create([
        'workflow_template_step_id' => $step->id,
        'role_id' => $roleId,
    ]);

    $subject = DummySubject::query()->create([
        'name' => 'Example Subject',
    ]);

    $instance = app(WorkflowEngine::class)->start(
        templateCode: 'MEMBERSHIP-APPLICATION',
        subject: $subject,
        context: ['started_by' => 1],
    );

    expect($instance->id)->not->toBeNull();
    expect($instance->workflow_template_id)->toBe($template->id);
    expect($instance->current_workflow_template_step_id)->toBe($step->id);
    expect($instance->current_workflow_instance_step_id)->not->toBeNull();
    expect($instance->is_closed)->toBeFalse();

    $this->assertDatabaseCount('workflow_instances', 1);
    $this->assertDatabaseCount('workflow_instance_steps', 1);
    $this->assertDatabaseCount('workflow_instance_step_assignments', 1);

    $this->assertDatabaseHas('workflow_instance_steps', [
        'workflow_instance_id' => $instance->id,
        'workflow_template_step_id' => $step->id,
        'sequence_no' => 1,
    ]);
});
