<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    protected $fillable = ['amount', 'type', 'category', 'payment_method', 'description', 'appointment_id', 'doctor_id', 'user_id', 'date'];

    protected $casts = [
        'date' => 'date',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
