<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'template_code',
        'template_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('laraflow.table_names.workflow_templates', 'workflow_templates');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowTemplateStep::class, 'workflow_template_id');
    }
}
