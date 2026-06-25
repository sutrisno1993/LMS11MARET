<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentDiscipline extends Model
{
    use HasFactory;

    protected $table = 'student_disciplines';

    protected $fillable = [
        'id_siswa',
        'tipe_tindakan',
        'tanggal_tindakan',
        'keterangan'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'id_siswa', 'id_siswa');
    }
}
