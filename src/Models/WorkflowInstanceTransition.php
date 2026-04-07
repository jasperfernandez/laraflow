<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowInstanceTransition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workflow_instance_id',
        'from_workflow_instance_step_id',
        'to_workflow_instance_step_id',
        'workflow_template_step_action_id',
        'action_id',
        'acted_by_person_id',
        'acted_by_position_id',
        'from_step_status_id',
        'to_step_status_id',
        'from_application_status_id',
        'to_application_status_id',
        'remarks',
        'metadata',
        'acted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'acted_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('laraflow.table_names.workflow_instance_transitions', 'workflow_instance_transitions');
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.workflow_instance'), 'workflow_instance_id');
    }

    public function fromStep(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.workflow_instance_step'), 'from_workflow_instance_step_id');
    }

    public function toStep(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.workflow_instance_step'), 'to_workflow_instance_step_id');
    }

    public function templateStepAction(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplateStepAction::class, 'workflow_template_step_action_id');
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.action'), 'action_id');
    }

    public function fromStepStatus(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.status'), 'from_step_status_id');
    }

    public function toStepStatus(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.status'), 'to_step_status_id');
    }

    public function fromApplicationStatus(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.status'), 'from_application_status_id');
    }

    public function toApplicationStatus(): BelongsTo
    {
        return $this->belongsTo(RefStatus::class, 'to_application_status_id');
    }
}
