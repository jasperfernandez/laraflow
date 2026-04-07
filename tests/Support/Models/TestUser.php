<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Tests\Support\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property array<int, string> $fakeRoles
 */
class TestUser extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'users';

    protected $guarded = [];

    /**
     * @var array<int, string>
     */
    public array $fakeRoles = [];

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->fakeRoles, true);
    }
}
