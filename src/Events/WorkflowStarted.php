<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JasperFernandez\Laraflow\Models\WorkflowInstance;

class WorkflowStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WorkflowInstance $instance,
    ) {}
}
