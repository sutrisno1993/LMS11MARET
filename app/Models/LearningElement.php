<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningElement extends Model
{
    use HasFactory;

    protected $table = 'learning_elements';
    protected $primaryKey = 'id_element';
    protected $guarded = [];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'id_guru', 'id_guru');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'id_mapel', 'id_mapel');
    }

    public function tps()
    {
        return $this->hasMany(LearningObjective::class, 'id_element', 'id_element');
    }
}
