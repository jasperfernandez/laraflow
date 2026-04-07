<?php

declare(strict_types=1);

use JasperFernandez\Laraflow\Models\WorkflowInstance;
use JasperFernandez\Laraflow\Models\WorkflowInstanceStep;
use JasperFernandez\Laraflow\Models\WorkflowInstanceStepAssignment;
use JasperFernandez\Laraflow\Models\WorkflowInstanceTransition;
use JasperFernandez\Laraflow\Tests\Support\Models\Action;
use JasperFernandez\Laraflow\Tests\Support\Models\Role;
use JasperFernandez\Laraflow\Tests\Support\Models\Status;

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
