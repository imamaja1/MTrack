<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberBenefit extends Model
{
    protected $fillable = [
        'admin_id',
        'min_purchase',
        'discount_nominal',
        'cashback_nominal',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
