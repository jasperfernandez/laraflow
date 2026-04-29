<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use JasperFernandez\Laraflow\Exceptions\UnauthorizedActionException;
use JasperFernandez\Laraflow\Models\WorkflowTemplate;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStep;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStepAction;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStepAssignment;
use JasperFernandez\Laraflow\Services\WorkflowEngine;
use JasperFernandez\Laraflow\Tests\Support\Models\Action;
use JasperFernandez\Laraflow\Tests\Support\Models\DummySubject;
use JasperFernandez\Laraflow\Tests\Support\Models\Status;
use JasperFernandez\Laraflow\Tests\Support\Models\TestUser;

it('throws when actor is not authorized for the current step', function () {
    $memberRoleId = DB::table('roles')->insertGetId([
        'name' => 'member',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $submitAction = Action::query()->create([
        'code' => 'submit_application',
        'name' => 'Submit Application',
    ]);

    $completedStatus = Status::query()->create([
        'code' => 'completed',
        'name' => 'Completed',
    ]);

    $nextAppStatus = Status::query()->create([
        'code' => 'pending_eligibility_verification',
        'name' => 'Pending Eligibility Verification',
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
        'sequence_no' => 1,
        'is_active' => true,
    ]);

    WorkflowTemplateStepAssignment::query()->create([
        'workflow_template_step_id' => $step->id,
        'role_id' => $memberRoleId,
    ]);

    WorkflowTemplateStepAction::query()->create([
        'workflow_template_step_id' => $step->id,
        'action_id' => $submitAction->id,
        'next_workflow_template_step_id' => null,
        'completes_step' => true,
        'resulting_step_status_id' => $completedStatus->id,
        'resulting_subject_status_id' => $nextAppStatus->id,
        'closes_workflow' => false,
    ]);

    $subject = DummySubject::query()->create(['name' => 'Example Subject']);

    $user = TestUser::query()->create([
        'name' => 'Unauthorized User',
        'email' => 'unauthorized@example.test',
        'password' => 'password',
    ]);

    $user->fakeRoles = ['other_role'];

    $engine = app(WorkflowEngine::class);

    $instance = $engine->start('MEMBERSHIP-APPLICATION', $subject);

    expect(fn () => $engine->apply($instance->fresh(), 'submit_application', $user))
        ->toThrow(UnauthorizedActionException::class);
});
