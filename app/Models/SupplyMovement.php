<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyMovement extends Model
{
    protected $fillable = ['supply_id', 'patient_id', 'type', 'quantity', 'reason', 'description', 'user_id', 'date'];

    protected $casts = ['date' => 'date'];

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
