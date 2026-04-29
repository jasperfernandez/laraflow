# jasperfernandez/laraflow

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jasperfernandez/laraflow.svg?style=flat-square)](https://packagist.org/packages/jasperfernandez/laraflow)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jasperfernandez/laraflow/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/jasperfernandez/laraflow/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jasperfernandez/laraflow/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/jasperfernandez/laraflow/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/jasperfernandez/laraflow.svg?style=flat-square)](https://packagist.org/packages/jasperfernandez/laraflow)

Laraflow is a workflow engine for Laravel applications. It lets you define workflow templates with ordered steps, assign roles to each step, configure allowed actions, and run workflow instances against any Eloquent model.

It is a good fit for application flows such as membership approvals, onboarding, request routing, document review, or any process that needs step history, assignments, status transitions, and audit trails.

## Installation

Install the package with Composer:

```bash
composer require jasperfernandez/laraflow
```

Publish and run the package migrations:

```bash
php artisan vendor:publish --tag="laraflow-migrations"
php artisan migrate
```

Publish the config file:

```bash
php artisan vendor:publish --tag="laraflow-config"
```

## Before You Start

Laraflow manages workflow templates, workflow instances, workflow steps, assignments, and transitions. Your application is responsible for the domain records that Laraflow references:

- roles
- statuses
- actions
- the subject model you want to attach a workflow to

By default, the package checks whether the acting user can execute a step by calling `hasRole(string $role)` on the authenticated user model. If your user model does not expose that method, the default authorization will deny the action.

## Configuration

After publishing the config, point the package at your own role, status, and action models:

```php
<?php

use App\Models\Action;
use App\Models\Role;
use App\Models\Status;
use JasperFernandez\Laraflow\Models\WorkflowInstance;
use JasperFernandez\Laraflow\Models\WorkflowInstanceStep;
use JasperFernandez\Laraflow\Models\WorkflowInstanceStepAssignment;
use JasperFernandez\Laraflow\Models\WorkflowInstanceTransition;

return [
    'table_names' => [
        'workflow_templates' => 'workflow_templates',
        'workflow_template_steps' => 'workflow_template_steps',
        'workflow_template_step_assignments' => 'workflow_template_step_assignments',
        'workflow_template_step_actions' => 'workflow_template_step_actions',
        'workflow_instances' => 'workflow_instances',
        'workflow_instance_steps' => 'workflow_instance_steps',
        'workflow_instance_step_assignments' => 'workflow_instance_step_assignments',
        'workflow_instance_transitions' => 'workflow_instance_transitions',
    ],

    'column_names' => [
        'model_morph_key' => 'subject_id',
        'model_morph_type' => 'subject_type',
    ],

    'models' => [
        'role' => Role::class,
        'status' => Status::class,
        'action' => Action::class,
        'workflow_instance' => WorkflowInstance::class,
        'workflow_instance_step' => WorkflowInstanceStep::class,
        'workflow_instance_step_assignment' => WorkflowInstanceStepAssignment::class,
        'workflow_instance_transition' => WorkflowInstanceTransition::class,
    ],
];
```

Your application models should provide these fields:

- `Role`: `id`, `name`
- `Status`: `id`, `code`, `name`
- `Action`: `id`, `code`, `name`

## Defining A Workflow

A workflow is defined with four main records:

1. A `WorkflowTemplate`
2. One or more `WorkflowTemplateStep` records
3. One or more `WorkflowTemplateStepAssignment` records that assign roles to each step
4. One or more `WorkflowTemplateStepAction` records that define which actions are allowed and where they lead

Example:

```php
use App\Models\Action;
use App\Models\Role;
use App\Models\Status;
use JasperFernandez\Laraflow\Models\WorkflowTemplate;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStep;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStepAction;
use JasperFernandez\Laraflow\Models\WorkflowTemplateStepAssignment;

$memberRole = Role::firstOrCreate(['name' => 'member']);
$reviewerRole = Role::firstOrCreate(['name' => 'reviewer']);

$completedStep = Status::firstOrCreate(
    ['code' => 'completed'],
    ['name' => 'Completed'],
);

$pendingReview = Status::firstOrCreate(
    ['code' => 'pending_eligibility_verification'],
    ['name' => 'Pending Eligibility Verification'],
);

$approvedStep = Status::firstOrCreate(
    ['code' => 'approved_step'],
    ['name' => 'Approved Step'],
);

$approvedApplication = Status::firstOrCreate(
    ['code' => 'approved'],
    ['name' => 'Approved'],
);

$submitAction = Action::firstOrCreate(
    ['code' => 'submit_application'],
    ['name' => 'Submit Application'],
);

$approveAction = Action::firstOrCreate(
    ['code' => 'approve_application'],
    ['name' => 'Approve Application'],
);

$template = WorkflowTemplate::create([
    'template_code' => 'MEMBERSHIP-APPLICATION',
    'template_name' => 'Membership Application Workflow',
    'description' => 'Workflow for membership application approvals',
    'is_active' => true,
]);

$registrationStep = WorkflowTemplateStep::create([
    'workflow_template_id' => $template->id,
    'step_code' => 'APPLICANT_REGISTRATION',
    'step_name' => 'Applicant Registration',
    'step_description' => 'Initial registration step',
    'sequence_no' => 1,
    'is_active' => true,
]);

$reviewStep = WorkflowTemplateStep::create([
    'workflow_template_id' => $template->id,
    'step_code' => 'ELIGIBILITY_REVIEW',
    'step_name' => 'Eligibility Review',
    'step_description' => 'Review submitted application',
    'sequence_no' => 2,
    'is_active' => true,
]);

WorkflowTemplateStepAssignment::create([
    'workflow_template_step_id' => $registrationStep->id,
    'role_id' => $memberRole->id,
]);

WorkflowTemplateStepAssignment::create([
    'workflow_template_step_id' => $reviewStep->id,
    'role_id' => $reviewerRole->id,
]);

WorkflowTemplateStepAction::create([
    'workflow_template_step_id' => $registrationStep->id,
    'action_id' => $submitAction->id,
    'next_workflow_template_step_id' => $reviewStep->id,
    'completes_step' => true,
    'resulting_step_status_id' => $completedStep->id,
    'resulting_subject_status_id' => $pendingReview->id,
    'closes_workflow' => false,
]);

WorkflowTemplateStepAction::create([
    'workflow_template_step_id' => $reviewStep->id,
    'action_id' => $approveAction->id,
    'next_workflow_template_step_id' => null,
    'completes_step' => true,
    'resulting_step_status_id' => $approvedStep->id,
    'resulting_subject_status_id' => $approvedApplication->id,
    'closes_workflow' => true,
]);
```

## Starting A Workflow

Use `WorkflowEngine::start()` to create a workflow instance for any Eloquent model:

```php
use App\Models\Application;
use JasperFernandez\Laraflow\Services\WorkflowEngine;

$application = Application::findOrFail(1);

$instance = app(WorkflowEngine::class)->start(
    templateCode: 'MEMBERSHIP-APPLICATION',
    subject: $application,
    context: [
        'started_by' => auth()->id(),
        'channel' => 'portal',
    ],
);
```

When a workflow starts, Laraflow:

- resolves the active template by `template_code`
- picks the lowest step `sequence_no`
- creates a workflow instance
- opens the first runtime step
- creates runtime assignments for the valid roles on that step

## Applying An Action

Use `WorkflowEngine::apply()` to execute an action on the current step:

```php
use App\Models\User;
use JasperFernandez\Laraflow\Data\TransitionPayload;
use JasperFernandez\Laraflow\Services\WorkflowEngine;

$actor = User::findOrFail(1);

$result = app(WorkflowEngine::class)->apply(
    instance: $instance->fresh(),
    actionCode: 'submit_application',
    actor: $actor,
    payload: new TransitionPayload(
        remarks: 'Submitted for eligibility review',
        metadata: ['channel' => 'portal'],
    ),
);
```

The returned `TransitionResult` includes:

- `instance`: the updated workflow instance
- `fromStep`: the step that was acted on
- `toStep`: the next opened step, or `null` if the workflow was closed
- `transition`: the recorded transition row
- `closed`: whether the workflow is now closed

## Authorization

The default authorization strategy is role-based. The package checks the assigned role names on the current step and calls `hasRole()` on the actor.

Example:

```php
class User extends Authenticatable
{
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }
}
```

If you want a different authorization strategy, bind your own implementation of `JasperFernandez\Laraflow\Contracts\WorkflowAuthorization` in your application container.

## End-To-End Example

```php
use App\Models\Application;
use App\Models\User;
use JasperFernandez\Laraflow\Data\TransitionPayload;
use JasperFernandez\Laraflow\Services\WorkflowEngine;

$engine = app(WorkflowEngine::class);

$application = Application::create([
    'name' => 'Jane Doe',
]);

$instance = $engine->start(
    templateCode: 'MEMBERSHIP-APPLICATION',
    subject: $application,
    context: ['started_by' => auth()->id()],
);

$member = User::findOrFail(1);

$firstResult = $engine->apply(
    instance: $instance->fresh(),
    actionCode: 'submit_application',
    actor: $member,
    payload: new TransitionPayload(
        remarks: 'Application submitted',
    ),
);

$reviewer = User::findOrFail(2);

$finalResult = $engine->apply(
    instance: $firstResult->instance->fresh(),
    actionCode: 'approve_application',
    actor: $reviewer,
    payload: new TransitionPayload(
        remarks: 'Application approved',
    ),
);

if ($finalResult->closed) {
    // The workflow has finished.
}
```

## Subject Integration

To easily manage workflows on your models, use the `HasWorkflows` trait:

```php
use Illuminate\Database\Eloquent\Model;
use JasperFernandez\Laraflow\Traits\HasWorkflows;

class Application extends Model
{
    use HasWorkflows;
}

// Usage
$application = Application::find(1);
$currentWorkflow = $application->currentWorkflow;
$allWorkflows = $application->workflowInstances;
```

## Events

Laraflow dispatches the following events during the workflow lifecycle:

| Event | Dispatched When |
|-------|-----------------|
| `WorkflowStarted` | A new workflow instance is initialized. |
| `WorkflowTransitioned` | An action is successfully applied to a step. |
| `WorkflowClosed` | A workflow instance is marked as closed. |

You can listen to these events in your `EventServiceProvider`:

```php
use JasperFernandez\Laraflow\Events\WorkflowTransitioned;

public function boot()
{
    Event::listen(WorkflowTransitioned::class, function ($event) {
        // $event->result is a TransitionResult DTO
    });
}
```

## Notes

- Use `WorkflowEngine` as the main entry point.
- The package can attach workflows to any Eloquent model through Laravel morph relationships.
- Workflow definitions are loaded from the database through the default repository implementation.
- Inactive workflow templates are ignored.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Jasper Fernandez](https://github.com/jasperfernandez)

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.
