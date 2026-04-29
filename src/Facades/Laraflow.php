<?php

namespace JasperFernandez\Laraflow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JasperFernandez\Laraflow\Services\WorkflowEngine
 */
class Laraflow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laraflow';
    }
}
