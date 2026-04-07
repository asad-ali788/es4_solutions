<?php

namespace App\Models;

use App\Traits\HasProfilePhoto;
use App\Traits\HasRevokedPermissions;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements AuthorizableContract
{
    use HasFactory, Notifiable, SoftDeletes;
    use HasProfilePhoto;
    // Alias Spatie's hasPermissionTo to spatieHasPermissionTo
    use HasRoles, HasRevokedPermissions {
        HasRoles::hasPermissionTo as protected spatieHasPermissionTo;
        HasRevokedPermissions::hasPermissionTo insteadof HasRoles;
    }

    protected $fillable = [
        'name',
        'email',
        'reporting_to',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',  // This will call getProfilePhotoUrlAttribute() automatically.
    ];

    // ------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------
    public function inboundShipments()
    {
        return $this->hasMany(InboundShipment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'id');
    }

    public function reportingManager()
    {
        return $this->belongsTo(User::class, 'reporting_to');
    }

    public function canImpersonate(): bool
    {
        $allowedUserIds = array_map('intval', config('impersonation.allowed_user_ids', []));

        return in_array((int) $this->id, $allowedUserIds, true);
    }

    public function canBeImpersonated(): bool
    {
        return true;
    }

}
