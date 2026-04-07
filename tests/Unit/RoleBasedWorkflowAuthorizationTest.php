<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use JasperFernandez\Laraflow\Data\ActionData;
use JasperFernandez\Laraflow\Data\StepData;
use JasperFernandez\Laraflow\Models\WorkflowInstance;
use JasperFernandez\Laraflow\Services\RoleBasedWorkflowAuthorization;
use JasperFernandez\Laraflow\Tests\Support\Models\TestUser;

it('authorizes actors that hold one of the assigned roles', function () {
    $instance = new WorkflowInstance;
    $step = authorizationStepData(['member', 'reviewer']);
    $action = authorizationActionData();

    $actor = TestUser::query()->create([
        'name' => 'Reviewer',
        'email' => 'reviewer@example.test',
        'password' => 'password',
    ]);

    $actor->fakeRoles = ['reviewer'];

    expect(app(RoleBasedWorkflowAuthorization::class)->canExecute($instance, $step, $action, $actor))->toBeTrue();
});

it('rejects actors that do not expose a hasRole method', function () {
    $instance = new WorkflowInstance;
    $step = authorizationStepData(['member']);
    $action = authorizationActionData();

    $actor = new class implements Authenticatable
    {
        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthIdentifier(): mixed
        {
            return 1;
        }

        public function getAuthPasswordName(): string
        {
            return 'password';
        }

        public function getAuthPassword(): ?string
        {
            return null;
        }

        public function getRememberToken(): ?string
        {
            return null;
        }

        public function setRememberToken($value): void {}

        public function getRememberTokenName(): string
        {
            return 'remember_token';
        }
    };

    expect(app(RoleBasedWorkflowAuthorization::class)->canExecute($instance, $step, $action, $actor))->toBeFalse();
});

it('rejects actors that do not hold any assigned role', function () {
    $instance = new WorkflowInstance;
    $step = authorizationStepData(['member']);
    $action = authorizationActionData();

    $actor = TestUser::query()->create([
        'name' => 'Guest',
        'email' => 'guest@example.test',
        'password' => 'password',
    ]);

    $actor->fakeRoles = ['guest'];

    expect(app(RoleBasedWorkflowAuthorization::class)->canExecute($instance, $step, $action, $actor))->toBeFalse();
});

function authorizationStepData(array $roleNames): StepData
{
    return new StepData(
        id: 1,
        code: 'STEP',
        name: 'Step',
        sequenceNo: 1,
        assignmentRoleNames: $roleNames,
        actions: ['submit' => authorizationActionData()],
    );
}

function authorizationActionData(): ActionData
{
    return new ActionData(
        templateStepActionId: 1,
        actionId: 1,
        actionCode: 'submit',
        nextTemplateStepId: 2,
        nextStepCode: 'NEXT',
        completesStep: true,
        closesApplication: false,
        resultingStepStatusId: 3,
        resultingStepStatusCode: 'completed',
        resultingApplicationStatusId: 4,
        resultingApplicationStatusCode: 'pending',
    );
}
