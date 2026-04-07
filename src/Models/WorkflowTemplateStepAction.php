<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $workflow_template_step_id
 * @property int $action_id
 * @property int|null $next_workflow_template_step_id
 * @property bool $completes_step
 * @property int|null $resulting_step_status_id
 * @property int|null $resulting_application_status_id
 * @property bool $closes_application
 * @property-read WorkflowTemplateStep $templateStep
 * @property-read Model $action
 * @property-read WorkflowTemplateStep|null $nextTemplateStep
 * @property-read Model|null $resultingStepStatus
 * @property-read Model|null $resultingApplicationStatus
 */
class WorkflowTemplateStepAction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workflow_template_step_id',
        'action_id',
        'next_workflow_template_step_id',
        'completes_step',
        'resulting_step_status_id',
        'resulting_application_status_id',
        'closes_application',
    ];

    protected $casts = [
        'completes_step' => 'boolean',
        'closes_application' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('laraflow.table_names.workflow_template_step_actions', 'workflow_template_step_actions');
    }

    public function templateStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplateStep::class, 'workflow_template_step_id');
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.action'), 'action_id');
    }

    public function nextTemplateStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplateStep::class, 'next_workflow_template_step_id');
    }

    public function resultingStepStatus(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.status'), 'resulting_step_status_id');
    }

    public function resultingApplicationStatus(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.status'), 'resulting_application_status_id');
    }
}
