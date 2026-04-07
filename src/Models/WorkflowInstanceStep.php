<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowInstanceStep extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workflow_instance_id',
        'workflow_template_step_id',
        'sequence_no',
        'status_id',
        'remarks',
        'opened_at',
        'completed_at',
        'closed_at',
    ];

    protected $casts = [
        'sequence_no' => 'integer',
        'opened_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('laraflow.table_names.workflow_instance_steps', 'workflow_instance_steps');
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.workflow_instance'), 'workflow_instance_id');
    }

    public function templateStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplateStep::class, 'workflow_template_step_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.status'), 'status_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(config('laraflow.models.workflow_instance_step_assignment'));
    }

    public function outgoingTransitions(): HasMany
    {
        return $this->hasMany(config('laraflow.models.workflow_instance_transition'), 'from_workflow_instance_step_id');
    }

    public function incomingTransitions(): HasMany
    {
        return $this->hasMany(config('laraflow.models.workflow_instance_transition'), 'to_workflow_instance_step_id');
    }
}
