<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workflow_template_id
 * @property string $subject_type
 * @property int|string $subject_id
 * @property int|null $current_workflow_instance_step_id
 * @property int|null $current_workflow_template_step_id
 * @property int|null $subject_status_id
 * @property bool $is_closed
 * @property array|null $context
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $closed_at
 * @property-read WorkflowTemplate $template
 * @property-read WorkflowInstanceStep|null $currentStep
 * @property-read WorkflowTemplateStep|null $currentTemplateStep
 * @property-read Model|null $subjectStatus
 * @property-read Collection<int, WorkflowInstanceStep> $steps
 * @property-read Collection<int, WorkflowInstanceTransition> $transitions
 */
class WorkflowInstance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workflow_template_id',
        'subject_type',
        'subject_id',
        'current_workflow_instance_step_id',
        'current_workflow_template_step_id',
        'subject_status_id',
        'is_closed',
        'started_at',
        'completed_at',
        'closed_at',
        'context',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'context' => 'array',
    ];

    public function getTable(): string
    {
        return config('laraflow.table_names.workflow_instances', 'workflow_instances');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'workflow_template_id');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(self::getModelClass('workflow_instance_step'), 'current_workflow_instance_step_id');
    }

    public function currentTemplateStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplateStep::class, 'current_workflow_template_step_id');
    }

    public function subjectStatus(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.status'), 'subject_status_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(self::getModelClass('workflow_instance_step'));
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(self::getModelClass('workflow_instance_transition'));
    }

    protected static function getModelClass(string $key): string
    {
        return config("laraflow.models.$key");
    }
}
