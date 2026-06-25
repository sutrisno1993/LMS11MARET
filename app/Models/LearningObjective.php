<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningObjective extends Model
{
    use HasFactory;

    protected $table = 'learning_objectives';
    protected $primaryKey = 'id_tp';

    protected $guarded = [];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'id_guru', 'id_guru');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'id_mapel', 'id_mapel');
    }

    public function grades()
    {
        return $this->hasMany(StudentGrade::class, 'id_tp', 'id_tp');
    }

    public function classes()
    {
        return $this->belongsToMany(Clas::class, 'learning_objective_class', 'id_tp', 'id_kelas');
    }

    public function element()
    {
        return $this->belongsTo(LearningElement::class, 'id_element', 'id_element');
    }

    public function topics()
    {
        return $this->hasMany(LearningTopic::class, 'id_tp', 'id_tp');
    }
}
