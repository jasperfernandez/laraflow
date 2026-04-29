<?php

declare(strict_types=1);

use JasperFernandez\Laraflow\Contracts\WorkflowDefinitionRepository;
use JasperFernandez\Laraflow\Data\ActionData;
use JasperFernandez\Laraflow\Data\StepData;
use JasperFernandez\Laraflow\Data\TemplateData;
use JasperFernandez\Laraflow\Data\TransitionPayload;
use JasperFernandez\Laraflow\Exceptions\InvalidActionException;
use JasperFernandez\Laraflow\Exceptions\UnauthorizedActionException;
use JasperFernandez\Laraflow\Exceptions\WorkflowDefinitionException;
use JasperFernandez\Laraflow\Exceptions\WorkflowStateException;
use JasperFernandez\Laraflow\Models\WorkflowInstance;
use JasperFernandez\Laraflow\Models\WorkflowTemplate;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStep;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStepAction;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStepAssignment;
use JasperFernandez\Laraflow\Services\WorkflowEngine;
use JasperFernandez\Laraflow\Tests\Support\Models\Action;
use JasperFernandez\Laraflow\Tests\Support\Models\DummySubject;
use JasperFernandez\Laraflow\Tests\Support\Models\Role;
use JasperFernandez\Laraflow\Tests\Support\Models\Status;
use JasperFernandez\Laraflow\Tests\Support\Models\TestUser;

it('throws when applying an action to a closed workflow instance', function () {
    [$instance, $actor] = buildWorkflowRuntime();

    $instance->forceFill(['is_closed' => true])->save();

    expect(fn () => app(WorkflowEngine::class)->apply($instance->fresh(), 'submit_application', $actor))
        ->toThrow(WorkflowStateException::class, 'Workflow instance is already closed.');
});

it('throws when the workflow instance has no current template step', function () {
    [$instance, $actor] = buildWorkflowRuntime();

    $instance->forceFill(['current_workflow_template_step_id' => null])->save();

    expect(fn () => app(WorkflowEngine::class)->apply($instance->fresh(), 'submit_application', $actor))
        ->toThrow(WorkflowStateException::class, 'Workflow instance has no current template step.');
});

it('throws when the current template can no longer be resolved', function () {
    [$instance, $actor] = buildWorkflowRuntime();

    $instance->template()->update(['is_active' => false]);

    expect(fn () => app(WorkflowEngine::class)->apply($instance->fresh(), 'submit_application', $actor))
        ->toThrow(WorkflowDefinitionException::class, 'Workflow template [MEMBERSHIP-APPLICATION] was not found.');
});

it('throws when the workflow instance has no current runtime step', function () {
    [$instance, $actor] = buildWorkflowRuntime();

    WorkflowInstance::query()->whereKey($instance->id)->update([
        'current_workflow_instance_step_id' => null,
    ]);

    expect(fn () => app(WorkflowEngine::class)->apply($instance->fresh(), 'submit_application', $actor))
        ->toThrow(WorkflowStateException::class, 'Workflow instance has no current runtime step.');
});

it('throws when an action is not allowed for the current step', function () {
    [$instance, $actor] = buildWorkflowRuntime();

    expect(fn () => app(WorkflowEngine::class)->apply($instance->fresh(), 'unknown_action', $actor))
        ->toThrow(InvalidActionException::class, 'Action [unknown_action] is not allowed for step [APPLICANT_REGISTRATION].');
});

it('throws when an actor is not authorized for the current step', function () {
    [$instance] = buildWorkflowRuntime();

    $actor = TestUser::query()->create([
        'name' => 'Unauthorized User',
        'email' => 'unauthorized@example.test',
        'password' => 'password',
    ]);

    $actor->fakeRoles = ['guest'];

    expect(fn () => app(WorkflowEngine::class)->apply($instance->fresh(), 'submit_application', $actor))
        ->toThrow(UnauthorizedActionException::class, 'Actor is not allowed to execute [submit_application] on step [APPLICANT_REGISTRATION].');
});

it('moves the workflow to the next step and records the transition payload', function () {
    [$instance, $actor, $fixture] = buildWorkflowRuntime();

    $payload = new TransitionPayload(
        remarks: 'Submitted for review',
        metadata: ['channel' => 'portal'],
    );

    $result = app(WorkflowEngine::class)->apply($instance->fresh(), 'submit_application', $actor, $payload);

    expect($result->closed)->toBeFalse()
        ->and($result->toStep)->not->toBeNull()
        ->and($result->fromStep->completed_at)->not->toBeNull()
        ->and($result->fromStep->closed_at)->not->toBeNull()
        ->and($result->fromStep->status_id)->toBe($fixture['statuses']['completed']->id)
        ->and($result->instance->current_workflow_template_step_id)->toBe($fixture['steps']['eligibility']->id)
        ->and($result->instance->subject_status_id)->toBe($fixture['statuses']['pending']->id)
        ->and($result->instance->is_closed)->toBeFalse()
        ->and($result->transition->to_workflow_instance_step_id)->toBe($result->toStep?->id)
        ->and($result->transition->actor_id)->toBe($actor->id)
        ->and($result->transition->actor_type)->toBe($actor->getMorphClass())
        ->and($result->transition->remarks)->toBe('Submitted for review')
        ->and($result->transition->metadata)->toBe(['channel' => 'portal']);

    $this->assertDatabaseHas('workflow_instance_steps', [
        'id' => $result->toStep?->id,
        'workflow_template_step_id' => $fixture['steps']['eligibility']->id,
        'sequence_no' => 2,
    ]);

    $this->assertDatabaseHas('workflow_instance_step_assignments', [
        'workflow_instance_step_id' => $result->toStep?->id,
        'role_id' => $fixture['roles']['reviewer']->id,
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('workflow_instance_transitions', [
        'id' => $result->transition->id,
        'workflow_instance_id' => $instance->id,
        'from_workflow_instance_step_id' => $result->fromStep->id,
        'to_workflow_instance_step_id' => $result->toStep?->id,
        'workflow_template_step_action_id' => $fixture['actions']['submit']->pivot->id,
        'action_id' => $fixture['actions']['submit']->model->id,
        'from_step_status_id' => null,
        'to_step_status_id' => $fixture['statuses']['completed']->id,
        'from_subject_status_id' => null,
        'to_subject_status_id' => $fixture['statuses']['pending']->id,
    ]);
});

it('can close the workflow without opening a new step', function () {
    [$instance, $actor, $fixture] = buildWorkflowRuntime();

    $firstTransition = app(WorkflowEngine::class)->apply($instance->fresh(), 'submit_application', $actor);
    $closedResult = app(WorkflowEngine::class)->apply(
        $firstTransition->instance->fresh(),
        'approve_application',
        $actor,
        new TransitionPayload(remarks: 'Approved'),
    );

    expect($closedResult->closed)->toBeTrue()
        ->and($closedResult->toStep)->toBeNull()
        ->and($closedResult->instance->is_closed)->toBeTrue()
        ->and($closedResult->instance->subject_status_id)->toBe($fixture['statuses']['approved']->id)
        ->and($closedResult->instance->current_workflow_template_step_id)->toBe($fixture['steps']['eligibility']->id)
        ->and($closedResult->instance->completed_at)->not->toBeNull()
        ->and($closedResult->instance->closed_at)->not->toBeNull()
        ->and($closedResult->fromStep->status_id)->toBe($fixture['statuses']['approved_step']->id)
        ->and($closedResult->transition->to_workflow_instance_step_id)->toBeNull()
        ->and($closedResult->transition->remarks)->toBe('Approved');

    $this->assertDatabaseCount('workflow_instance_steps', 2);
    $this->assertDatabaseHas('workflow_instance_transitions', [
        'id' => $closedResult->transition->id,
        'to_workflow_instance_step_id' => null,
        'to_subject_status_id' => $fixture['statuses']['approved']->id,
    ]);
});

it('throws when the next step definition referenced by an action is missing', function () {
    [$instance, $actor, $fixture] = buildWorkflowRuntime();

    $missingNextStepId = $fixture['steps']['eligibility']->id + 999;

    app()->bind(WorkflowDefinitionRepository::class, function () use ($fixture, $missingNextStepId) {
        return new class($fixture, $missingNextStepId) implements WorkflowDefinitionRepository
        {
            public function __construct(
                private array $fixture,
                private int $missingNextStepId,
            ) {}

            public function findByTemplateCode(string $templateCode): ?TemplateData
            {
                if ($templateCode !== 'MEMBERSHIP-APPLICATION') {
                    return null;
                }

                return new TemplateData(
                    id: $this->fixture['template']->id,
                    code: 'MEMBERSHIP-APPLICATION',
                    name: 'Membership Application Workflow',
                    steps: [
                        new StepData(
                            id: $this->fixture['steps']['registration']->id,
                            code: 'APPLICANT_REGISTRATION',
                            name: 'Applicant Registration',
                            sequenceNo: 1,
                            assignmentRoleNames: ['member'],
                            actions: [
                                'submit_application' => new ActionData(
                                    templateStepActionId: $this->fixture['actions']['submit']->pivot->id,
                                    actionId: $this->fixture['actions']['submit']->model->id,
                                    actionCode: 'submit_application',
                                    nextTemplateStepId: $this->missingNextStepId,
                                    nextStepCode: 'MISSING_STEP',
                                    completesStep: true,
                                    closesWorkflow: false,
                                    resultingStepStatusId: $this->fixture['statuses']['completed']->id,
                                    resultingStepStatusCode: 'completed',
                                    resultingSubjectStatusId: $this->fixture['statuses']['pending']->id,
                                    resultingSubjectStatusCode: 'pending_eligibility_verification',
                                ),
                            ],
                        ),
                    ],
                );
            }
        };
    });

    app()->forgetInstance(WorkflowEngine::class);

    expect(fn () => app(WorkflowEngine::class)->apply($instance->fresh(), 'submit_application', $actor))
        ->toThrow(WorkflowDefinitionException::class, "Next step definition [{$missingNextStepId}] was not found.");
});

/**
 * @return array{0: WorkflowInstance, 1: TestUser, 2: array<string, mixed>}
 */
function buildWorkflowRuntime(): array
{
    $roles = [
        'member' => Role::query()->create(['name' => 'member']),
        'reviewer' => Role::query()->create(['name' => 'reviewer']),
    ];

    $statuses = [
        'completed' => Status::query()->create(['code' => 'completed', 'name' => 'Completed']),
        'pending' => Status::query()->create([
            'code' => 'pending_eligibility_verification',
            'name' => 'Pending Eligibility Verification',
        ]),
        'approved_step' => Status::query()->create(['code' => 'approved_step', 'name' => 'Approved Step']),
        'approved' => Status::query()->create(['code' => 'approved', 'name' => 'Approved']),
    ];

    $actions = [
        'submit' => Action::query()->create(['code' => 'submit_application', 'name' => 'Submit Application']),
        'approve' => Action::query()->create(['code' => 'approve_application', 'name' => 'Approve Application']),
    ];

    $template = WorkflowTemplate::query()->create([
        'template_code' => 'MEMBERSHIP-APPLICATION',
        'template_name' => 'Membership Application Workflow',
        'description' => 'Workflow for testing transitions',
        'is_active' => true,
    ]);

    $steps = [
        'registration' => WorkflowTemplateStep::query()->create([
            'workflow_template_id' => $template->id,
            'step_code' => 'APPLICANT_REGISTRATION',
            'step_name' => 'Applicant Registration',
            'sequence_no' => 1,
            'is_active' => true,
        ]),
        'eligibility' => WorkflowTemplateStep::query()->create([
            'workflow_template_id' => $template->id,
            'step_code' => 'ELIGIBILITY_REVIEW',
            'step_name' => 'Eligibility Review',
            'sequence_no' => 2,
            'is_active' => true,
        ]),
    ];

    WorkflowTemplateStepAssignment::query()->create([
        'workflow_template_step_id' => $steps['registration']->id,
        'role_id' => $roles['member']->id,
    ]);

    WorkflowTemplateStepAssignment::query()->create([
        'workflow_template_step_id' => $steps['eligibility']->id,
        'role_id' => $roles['reviewer']->id,
    ]);

    $submitPivot = WorkflowTemplateStepAction::query()->create([
        'workflow_template_step_id' => $steps['registration']->id,
        'action_id' => $actions['submit']->id,
        'next_workflow_template_step_id' => $steps['eligibility']->id,
        'completes_step' => true,
        'resulting_step_status_id' => $statuses['completed']->id,
        'resulting_subject_status_id' => $statuses['pending']->id,
        'closes_workflow' => false,
    ]);

    $approvePivot = WorkflowTemplateStepAction::query()->create([
        'workflow_template_step_id' => $steps['eligibility']->id,
        'action_id' => $actions['approve']->id,
        'next_workflow_template_step_id' => null,
        'completes_step' => true,
        'resulting_step_status_id' => $statuses['approved_step']->id,
        'resulting_subject_status_id' => $statuses['approved']->id,
        'closes_workflow' => true,
    ]);

    $subject = DummySubject::query()->create(['name' => 'Workflow Subject']);

    $actor = TestUser::query()->create([
        'name' => 'Authorized User',
        'email' => 'authorized@example.test',
        'password' => 'password',
    ]);

    $actor->fakeRoles = ['member', 'reviewer'];

    $instance = app(WorkflowEngine::class)->start('MEMBERSHIP-APPLICATION', $subject);

    return [
        $instance,
        $actor,
        [
            'roles' => $roles,
            'statuses' => $statuses,
            'steps' => $steps,
            'template' => $template,
            'actions' => [
                'submit' => (object) ['model' => $actions['submit'], 'pivot' => $submitPivot],
                'approve' => (object) ['model' => $actions['approve'], 'pivot' => $approvePivot],
            ],
        ],
    ];
}
