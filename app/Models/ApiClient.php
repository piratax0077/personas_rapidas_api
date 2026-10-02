<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ApiClient extends Model
{
    protected $fillable = ['name', 'client_id', 'secret_encrypted', 'permissions', 'allowed_ips', 'enabled', 'last_used_at'];

    protected $casts = [
        'permissions' => 'array',
        'allowed_ips' => 'array',
        'enabled' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function secret(): string
    {
        return Crypt::decryptString($this->secret_encrypted);
    }

    public function allows(string $permission): bool
    {
        return in_array('*', $this->permissions ?: [], true)
            || in_array($permission, $this->permissions ?: [], true);
    }
}
