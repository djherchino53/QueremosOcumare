<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalSpecialty extends Model
{
    protected $fillable = ['name', 'description'];

    public function doctors()
    {
        return $this->belongsToMany(User::class, 'doctor_specialty');
    }

    public function estudios()
    {
        return $this->hasMany(EspecialidadEstudio::class, 'especialidad_id');
    }
}
