<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EspecialidadEstudio extends Model
{
    protected $table = 'especialidad_estudios';

    protected $fillable = ['especialidad_id', 'estudio', 'costo'];

    public function specialty()
    {
        return $this->belongsTo(MedicalSpecialty::class, 'especialidad_id');
    }
}
