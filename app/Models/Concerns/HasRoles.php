<?php

namespace App\Models\Concerns;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string ...$names): bool
    {
        return $this->roles->whereIn('name', $names)->isNotEmpty();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN, Role::ACADEMY_ADMIN);
    }

    public function isTrainer(): bool
    {
        return $this->hasRole(Role::TRAINER);
    }

    public function isStudent(): bool
    {
        return $this->hasRole(Role::STUDENT);
    }

    public function assignRole(string $name): void
    {
        $role = Role::firstOrCreate(['name' => $name], ['label' => $name]);
        $this->roles()->syncWithoutDetaching([$role->id]);
        $this->unsetRelation('roles');
    }
}
