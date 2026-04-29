<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use JasperFernandez\Laraflow\Models\WorkflowInstance;

trait HasWorkflows
{
    /**
     * Get all workflow instances for this model.
     *
     * @return MorphMany<WorkflowInstance>
     */
    public function workflowInstances(): MorphMany
    {
        /** @var Model $this */
        return $this->morphMany(
            config('laraflow.models.workflow_instance', WorkflowInstance::class),
            'subject'
        );
    }

    /**
     * Get the current active workflow instance for this model.
     *
     * @return MorphOne<WorkflowInstance>
     */
    public function currentWorkflow(): MorphOne
    {
        /** @var Model $this */
        return $this->morphOne(
            config('laraflow.models.workflow_instance', WorkflowInstance::class),
            'subject'
        )->where('is_closed', false)->latestOfMany();
    }

    /**
     * Determine if the model has an active workflow.
     */
    public function hasActiveWorkflow(): bool
    {
        return $this->currentWorkflow()->exists();
    }
}
