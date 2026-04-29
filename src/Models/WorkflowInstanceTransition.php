<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $workflow_instance_id
 * @property int $from_workflow_instance_step_id
 * @property int|null $to_workflow_instance_step_id
 * @property int $workflow_template_step_action_id
 * @property int $action_id
 * @property string $actor_type
 * @property int|string $actor_id
 * @property int|null $from_step_status_id
 * @property int|null $to_step_status_id
 * @property int|null $from_application_status_id
 * @property int|null $to_application_status_id
 * @property string|null $remarks
 * @property array|null $metadata
 * @property-read WorkflowInstance $instance
 * @property-read WorkflowInstanceStep $fromStep
 * @property-read WorkflowInstanceStep|null $toStep
 * @property-read WorkflowTemplateStepAction $templateStepAction
 * @property-read Model $action
 * @property-read Model|null $fromStepStatus
 * @property-read Model|null $toStepStatus
 * @property-read Model|null $fromApplicationStatus
 * @property-read Model|null $toApplicationStatus
 */
class WorkflowInstanceTransition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workflow_instance_id',
        'from_workflow_instance_step_id',
        'to_workflow_instance_step_id',
        'workflow_template_step_action_id',
        'action_id',
        'actor_type',
        'actor_id',
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

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function toApplicationStatus(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.status'), 'to_application_status_id');
    }
}
