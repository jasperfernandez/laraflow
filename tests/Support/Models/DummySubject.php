<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 */
class DummySubject extends Model
{
    use SoftDeletes;

    protected $table = 'dummy_subjects';

    protected $guarded = [];
}
