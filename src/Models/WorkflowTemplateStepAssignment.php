<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowTemplateStepAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workflow_template_step_id',
        'role_id',
    ];

    public function getTable(): string
    {
        return config('laraflow.table_names.workflow_template_step_assignments', 'workflow_template_step_assignments');
    }

    public function templateStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplateStep::class, 'workflow_template_step_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(config('laraflow.models.role'), 'role_id');
    }
}
