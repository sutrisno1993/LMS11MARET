<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningTopic extends Model
{
    use HasFactory;

    protected $table = 'learning_topics';
    protected $primaryKey = 'id_topic';
    protected $guarded = [];

    public function tp()
    {
        return $this->belongsTo(LearningObjective::class, 'id_tp', 'id_tp');
    }
}
