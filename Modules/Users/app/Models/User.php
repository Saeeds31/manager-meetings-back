<?php

namespace Modules\Users\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Modules\Addresses\Models\Address;
use Modules\Register\Models\ImportantDocument;
use Modules\Register\Models\PhysicalCharacteristics;
use Modules\Wallet\Models\Wallet;
use Laravel\Sanctum\HasApiTokens;
use Modules\CourseOrder\Models\CourseOrder;
use Modules\Register\Models\IdentityDocument;
use Modules\Register\Models\Register;
use Modules\ResultExam\Models\ResultExam;

// use Modules\Users\Database\Factories\UserFactory;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'full_name',
        'mobile',
        'national_code',
        'birth_date',
        'birth_certificate_number',
        'marital_status',
        'place_of_residence',
        'father_name',
        'place_birth_certificate',
        'job_address',
        'phone',
        'front_national_cart',
        'back_national_cart',
        'birth_certificate_image',
        'image',
        'postal_code',
        'status'
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];
    /**
     * Get all addresses for the user.
     */
        public function validity()
    {
        return $this->hasOne(Validity::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
    public function permissions()
    {
        return $this->roles->map->permissions->flatten()->unique('id');
    }

    public function hasPermission($permission)
    {
        return $this->permissions()->contains('name', $permission);
    }
    public static  function dashboardReport()
    {
        return [
            'total_users'     => self::count(),
            'with_addresses'  => self::has('addresses')->count(),
            'with_wallet'     => self::has('wallet')->count(),
            'without_wallet'  => self::doesntHave('wallet')->count(),
            'today_registered' => self::whereDate('created_at', today())->count(),
        ];
    }
}
