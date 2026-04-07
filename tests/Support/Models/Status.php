<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 */
class Status extends Model
{
    use SoftDeletes;

    protected $table = 'statuses';

    protected $guarded = [];
}
