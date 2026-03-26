<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        /*
         * Needs: no runtime input; uses model cast configuration.
         * Does: defines attribute casting rules for this model.
         * Returns: a map of attribute names to cast types.
         */
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function site(): HasOne
    {
        /*
         * Needs: a persisted User model instance.
         * Does: defines one-to-one relation between user and site.
         * Returns: a HasOne relationship to Site.
         */
        return $this->hasOne(Site::class);
    }
}
