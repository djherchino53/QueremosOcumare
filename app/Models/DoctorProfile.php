<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    protected $fillable = ['user_id', 'bio', 'professional_id', 'working_days', 'working_hours'];

    protected $casts = [
        'working_days' => 'array',
        'working_hours' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
