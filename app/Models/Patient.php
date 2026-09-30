<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = ['name', 'dni', 'phone', 'address', 'email'];

    public function medicalHistories()
    {
        return $this->hasMany(MedicalHistory::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
