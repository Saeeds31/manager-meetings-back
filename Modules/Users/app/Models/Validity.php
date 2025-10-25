<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Users\Database\Factories\ValidityFactory;

class Validity extends Model
{
    use HasFactory;
    protected $table = "validity";

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'status',
        'to_date'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
