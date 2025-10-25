<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',   // مثل: manage_users, edit_products
        'label',  // توضیح فارسی یا نمایشی برای ادمین
    ];

    /**
     * نقش‌هایی که این دسترسی رو دارن
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }
}
