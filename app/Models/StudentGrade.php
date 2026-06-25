<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentGrade extends Model
{
    use HasFactory;

    protected $table = 'student_grades';
    protected $primaryKey = 'id_grade';

    protected $guarded = [];

    public function student()
    {
        return $this->belongsTo(Student::class, 'id_siswa', 'id_siswa');
    }

    public function learningObjective()
    {
        return $this->belongsTo(LearningObjective::class, 'id_tp', 'id_tp');
    }

    public function topic()
    {
        return $this->belongsTo(LearningTopic::class, 'id_topic', 'id_topic');
    }
}
