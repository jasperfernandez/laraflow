<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowInstanceStepAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workflow_instance_step_id',
        'role_id',
        'assigned_to_person_id',
        'assigned_to_position_id',
        'assigned_at',
        'unassigned_at',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'unassigned_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('laraflow.table_names.workflow_instance_step_assignments', 'workflow_instance_step_assignments');
    }

    public function instanceStep(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.workflow_instance_step'), 'workflow_instance_step_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.role'));
    }
}
