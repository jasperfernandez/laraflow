<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Data;

final readonly class TemplateData
{
    /**
     * @param  array<int, StepData>  $steps
     */
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public array $steps,
    ) {}

    public function firstStep(): ?StepData
    {
        if ($this->steps === []) {
            return null;
        }

        $steps = $this->steps;
        usort($steps, fn (StepData $a, StepData $b): int => $a->sequenceNo <=> $b->sequenceNo);

        return $steps[0];
    }

    public function findStepById(int $id): ?StepData
    {
        foreach ($this->steps as $step) {
            if ($step->id === $id) {
                return $step;
            }
        }

        return null;
    }

    public function findStepByCode(string $code): ?StepData
    {
        foreach ($this->steps as $step) {
            if ($step->code === $code) {
                return $step;
            }
        }

        return null;
    }
}
