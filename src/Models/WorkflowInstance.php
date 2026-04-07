<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowInstance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workflow_template_id',
        'subject_type',
        'subject_id',
        'current_workflow_instance_step_id',
        'current_workflow_template_step_id',
        'application_status_id',
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

    public function applicationStatus(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.status'), 'application_status_id');
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
        return config("laraflow.models.{$key}");
    }
}
