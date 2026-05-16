<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role',
        'permissions', 'default_collection_id', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'permissions' => 'array',
        'is_active'   => 'boolean',
    ];

    // ── Role helpers ──────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {return $this->role === 'admin';}
    public function isEditor(): bool
    {return $this->role === 'editor';}
    public function isViewer(): bool
    {return $this->role === 'viewer';}

    /** Check a permission flag e.g. 'run', 'write', 'ai', 'read' */
    public function hasPermission(string $permission): bool
    {
        // Admins have all permissions
        if ($this->isAdmin()) {
            return true;
        }

        $perms = $this->permissions ?? [];

        return in_array($permission, $perms);
    }
    // Default permissions by role (used when creating users)
    public static function defaultPermissions(string $role): array
    {
        return match ($role) {
            'admin'  => ['read', 'write', 'run', 'ai', 'manage_users'],
            'editor' => ['read', 'write', 'run', 'ai'],
            'viewer' => ['read'],
            default  => ['read'],
        };
    }

    public function toApiArray(): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'email'                 => $this->email,
            'role'                  => $this->role,
            'permissions'           => $this->permissions ?? [],
            'default_collection_id' => $this->default_collection_id,
            'is_active'             => $this->is_active,
            'created_at'            => $this->created_at?->toIso8601String(),
        ];
    }
}
