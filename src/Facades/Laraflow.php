<?php

namespace JasperFernandez\Laraflow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JasperFernandez\Laraflow\Laraflow
 */
class Laraflow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JasperFernandez\Laraflow\Laraflow::class;
    }
}
