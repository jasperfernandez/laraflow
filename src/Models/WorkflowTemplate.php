<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $template_code
 * @property string $template_name
 * @property string|null $description
 * @property bool $is_active
 * @property-read Collection<int, WorkflowTemplateStep> $steps
 */
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
