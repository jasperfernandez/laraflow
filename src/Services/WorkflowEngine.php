<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use JasperFernandez\Laraflow\Data\TransitionPayload;
use JasperFernandez\Laraflow\Data\TransitionResult;
use JasperFernandez\Laraflow\Models\WorkflowInstance;

final readonly class WorkflowEngine
{
    public function __construct(
        private WorkflowStarter $starter,
        private WorkflowTransitioner $transitioner,
    ) {}

    public function start(string $templateCode, Model $subject, array $context = []): WorkflowInstance
    {
        return $this->starter->start($templateCode, $subject, $context);
    }

    public function apply(
        WorkflowInstance $instance,
        string $actionCode,
        Authenticatable $actor,
        ?TransitionPayload $payload = null,
    ): TransitionResult {
        return $this->transitioner->apply($instance, $actionCode, $actor, $payload);
    }
}
