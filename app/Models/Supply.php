<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    protected $fillable = ['name', 'description', 'quantity', 'expiration_date'];

    protected $casts = [
        'expiration_date' => 'date',
    ];

    public function movements()
    {
        return $this->hasMany(SupplyMovement::class);
    }
}
