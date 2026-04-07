<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\SupplierProfile;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

     public function supplierProfile()
    {
        return $this->hasOne(SupplierProfile::class);
    }

    public function buyerProfile()
    {
        return $this->hasOne(BuyerProfile::class);
    }

    // Helper
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSupplier(): bool
    {
        return $this->role === 'supplier';
    }

    public function isBuyer(): bool
    {
        return $this->role === 'buyer';
    }

    public function isApprovedSupplier(): bool
    {
        return $this->isSupplier()
            && $this->supplierProfile?->approval_status === 'approved';
    }
}
