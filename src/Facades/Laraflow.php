<?php

namespace JasperFernandez\Laraflow\Facades;

use Illuminate\Support\Facades\Facade;
use JasperFernandez\Laraflow\Services\WorkflowEngine;

/**
 * @see WorkflowEngine
 */
class Laraflow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laraflow';
    }
}
