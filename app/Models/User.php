<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'type',
        'national_id',
        'full_name',
        'phone',
        'email',
        'auth_provider',
        'provider_id',
        'is_active',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function bankAccounts()
    {
        return $this->hasMany(CustomerAccount::class, 'customer_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'customer_id');
    }

    public function deductions()
    {
        return $this->hasMany(Deduction::class, 'customer_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function consents()
    {
        return $this->hasMany(Consent::class, 'customer_id');
    }

    public function orgUsers()
    {
        return $this->hasMany(OrgUser::class, 'user_id');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'org_users', 'user_id', 'org_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }
}
