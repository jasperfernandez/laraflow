<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Contracts;

use JasperFernandez\Laraflow\Data\TemplateData;

interface WorkflowDefinitionRepository
{
    public function findByTemplateCode(string $templateCode): ?TemplateData;
}
