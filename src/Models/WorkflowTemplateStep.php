<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $workflow_template_id
 * @property string $step_code
 * @property string $step_name
 * @property string|null $step_description
 * @property int $sequence_no
 * @property bool $is_active
 * @property-read WorkflowTemplate $template
 * @property-read Collection<int, WorkflowTemplateStepAssignment> $assignments
 * @property-read Collection<int, WorkflowTemplateStepAction> $actions
 */
class WorkflowTemplateStep extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workflow_template_id',
        'step_code',
        'step_name',
        'step_description',
        'sequence_no',
        'is_active',
    ];

    protected $casts = [
        'sequence_no' => 'integer',
        'is_active' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('laraflow.table_names.workflow_template_steps', 'workflow_template_steps');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'workflow_template_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkflowTemplateStepAssignment::class, 'workflow_template_step_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowTemplateStepAction::class, 'workflow_template_step_id');
    }
}
